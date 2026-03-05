<?php

namespace App\Http\Controllers;

use App\Models\BoostCampaign;
use App\Models\FacebookPage;
use App\Models\FacebookPost;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
        $pages = FacebookPage::where('is_active', true)->get();
        $selectedPageId = $request->get('page_id', $pages->first()?->page_id);

        $posts = FacebookPost::when($selectedPageId, function ($q) use ($selectedPageId, $pages) {
            $page = $pages->firstWhere('page_id', $selectedPageId);
            if ($page) $q->where('facebook_page_id', $page->id)->where('is_boostable', 1);
        })->orderByDesc('posted_at')->get();

        return view('campaigns.create', compact('pages', 'selectedPageId', 'posts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
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
            'interests'             => 'nullable|array',
            'optimization_goal'     => 'required|string',
            'billing_event'         => 'required|string',
            'bid_strategy'          => 'required|string',
            'ad_name'               => 'required|string|max:255',
            'post_id'               => 'required|string|max:100',
            'ad_status'             => 'required|in:PAUSED,ACTIVE',
        ]);

        $campaign = BoostCampaign::create([
            ...$validated,
            'user_id'          => auth()->id(),
            'execution_status' => 'pending',
        ]);

        // Envoyer l'ID au webhook n8n
        $this->triggerN8n($campaign);

        return response()->json([
            'success'     => true,
            'campaign_id' => $campaign->id,
            'redirect'    => route('campaigns.index'),
        ]);
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
