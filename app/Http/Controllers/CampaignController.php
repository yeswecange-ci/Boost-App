<?php

namespace App\Http\Controllers;

use App\Models\BoostCampaign;
use App\Models\FacebookPage;
use App\Models\FacebookPost;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CampaignController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user        = auth()->user();
        $isValidator = $user->hasRole(['validator_n1', 'validator_n2', 'validator', 'admin']);

        $query = BoostCampaign::with('user')->latest();

        if (!$isValidator) {
            $query->where('user_id', $user->id);
        }

        if ($status = $request->get('status')) {
            $query->where('execution_status', $status);
        }

        $campaigns = $query->paginate(15)->withQueryString();

        $counts = [
            'all'     => BoostCampaign::when(!$isValidator, fn($q) => $q->where('user_id', $user->id))->count(),
            'pending' => BoostCampaign::when(!$isValidator, fn($q) => $q->where('user_id', $user->id))->where('execution_status', 'pending')->count(),
            'running' => BoostCampaign::when(!$isValidator, fn($q) => $q->where('user_id', $user->id))->where('execution_status', 'running')->count(),
            'done'    => BoostCampaign::when(!$isValidator, fn($q) => $q->where('user_id', $user->id))->where('execution_status', 'done')->count(),
            'error'   => BoostCampaign::when(!$isValidator, fn($q) => $q->where('user_id', $user->id))->where('execution_status', 'error')->count(),
        ];

        return view('campaigns.index', compact('campaigns', 'counts', 'isValidator'));
    }

    public function create(Request $request)
    {
        $postId = $request->get('post_id');

        // Charger le post depuis posts_master si post_id fourni
        $post = null;
        if ($postId) {
            $post = FacebookPost::with('page')->where('post_id', $postId)->first();
        }

        // Si pas de post trouvé, lister tous les posts boostables pour sélection
        $posts = null;
        if (!$post) {
            $posts = FacebookPost::with('page')
                ->where('is_boostable', 1)
                ->orderByDesc('posted_at')
                ->get();
        }

        return view('campaigns.create', compact('post', 'posts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'post_id'               => 'required|string|max:100',
            'campaign_name'         => 'required|string|max:255',
            'campaign_objective'    => 'required|string',
            'special_ad_categories' => 'required|string',
            'campaign_status'       => 'required|in:PAUSED,ACTIVE',
            'existing_campaign_id'  => 'nullable|string|max:100',
            'adset_name'            => 'required|string|max:255',
            'budget_type'           => 'required|in:lifetime_budget,daily_budget',
            'budget_value'          => 'required|integer|min:500',
            'duration_days'         => 'required|integer|min:1|max:90',
            'countries'             => 'required|array|min:1',
            'interests_value'       => 'nullable|string',
            'optimization_goal'     => 'required|string',
            'billing_event'         => 'required|string',
            'bid_strategy'          => 'required|string',
            'ad_name'               => 'required|string|max:255',
            'ad_status'             => 'required|in:PAUSED,ACTIVE',
        ]);

        // Décoder les intérêts depuis le champ JSON caché
        $interests = null;
        if ($request->filled('interests_value')) {
            $interests = json_decode($request->interests_value, true) ?: null;
        }

        $campaign = BoostCampaign::create([
            'user_id'               => auth()->id(),
            'post_id'               => $request->post_id,
            'campaign_name'         => $request->campaign_name,
            'campaign_objective'    => $request->campaign_objective,
            'special_ad_categories' => $request->special_ad_categories,
            'campaign_status'       => $request->campaign_status,
            'existing_campaign_id'  => $request->existing_campaign_id,
            'adset_name'            => $request->adset_name,
            'budget_type'           => $request->budget_type,
            'budget_value'          => $request->budget_value,
            'duration_days'         => $request->duration_days,
            'countries'             => $request->countries,
            'interests'             => $interests,
            'optimization_goal'     => $request->optimization_goal,
            'billing_event'         => $request->billing_event,
            'bid_strategy'          => $request->bid_strategy,
            'ad_name'               => $request->ad_name,
            'ad_status'             => $request->ad_status,
            'execution_status'      => 'pending',
        ]);

        $this->triggerN8n($campaign);

        return redirect()->route('campaigns.show', $campaign->id)
            ->with('success', 'Campagne enregistrée et envoyée à n8n !');
    }

    public function show(BoostCampaign $campaign)
    {
        return view('campaigns.show', compact('campaign'));
    }

    // ── Callback n8n → Laravel (UPDATE execution_status + meta IDs) ──

    public function n8nCallback(Request $request)
    {
        // Vérification secret
        $secret = SettingService::get('n8n.secret');
        if ($secret && $request->header('X-N8N-Secret') !== $secret) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $campaign = BoostCampaign::find($request->input('campaign_db_id'));
        if (!$campaign) {
            return response()->json(['error' => 'Campaign not found'], 404);
        }

        $status = $request->input('execution_status', 'done');

        $campaign->update([
            'execution_status' => $status,
            'meta_campaign_id' => $request->input('meta_campaign_id'),
            'meta_adset_id'    => $request->input('meta_adset_id'),
            'meta_ad_id'       => $request->input('meta_ad_id'),
            'error_message'    => $request->input('error_message'),
            'launched_at'      => $status === 'done' ? now() : null,
        ]);

        return response()->json(['success' => true]);
    }

    // ── Envoi vers n8n ──

    private function triggerN8n(BoostCampaign $campaign): void
    {
        $mockMode   = SettingService::bool('n8n.mock_mode', true);
        $webhookUrl = SettingService::get('n8n.webhook_campaign');

        if ($mockMode || !$webhookUrl) {
            // Simulation : marquer done immédiatement
            $campaign->update([
                'execution_status' => 'done',
                'launched_at'      => now(),
                'meta_campaign_id' => 'mock_camp_' . $campaign->id,
                'meta_adset_id'    => 'mock_adset_' . $campaign->id,
                'meta_ad_id'       => 'mock_ad_' . $campaign->id,
            ]);
            return;
        }

        try {
            $campaign->update(['execution_status' => 'running']);

            $secret  = SettingService::get('n8n.secret');
            $timeout = (int) SettingService::get('n8n.timeout', 10);

            Http::timeout($timeout)
                ->withHeaders(array_filter(['X-N8N-Secret' => $secret]))
                ->post($webhookUrl, ['campaign_db_id' => $campaign->id]);

        } catch (\Throwable $e) {
            Log::error('CampaignController::triggerN8n', ['error' => $e->getMessage()]);
            $campaign->update([
                'execution_status' => 'error',
                'error_message'    => $e->getMessage(),
            ]);
        }
    }
}
