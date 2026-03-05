<?php

namespace App\Http\Controllers;

use App\Models\BoostCampaign;
use App\Models\FacebookPage;
use App\Models\FacebookPost;
use App\Models\User;
use App\Notifications\CampaignSubmittedNotification;
use App\Notifications\CampaignPendingN2Notification;
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

        $pageIds       = $user->scopedFacebookPageIds(); // null = admin
        $allowedPostIds = $pageIds !== null
            ? FacebookPost::whereIn('facebook_page_id', $pageIds)->pluck('post_id')
            : null;

        $query = BoostCampaign::with('user')->latest();

        if (!$isValidator) {
            $query->where('user_id', $user->id);
        }

        $query->when($allowedPostIds !== null, fn($q) => $q->whereIn('post_id', $allowedPostIds));

        if ($status = $request->get('status')) {
            if ($status === 'done') {
                $query->whereIn('execution_status', ['done', 'paused_ready', 'active']);
            } else {
                $query->where('execution_status', $status);
            }
        }

        $campaigns = $query->paginate(15)->withQueryString();

        $base = fn() => BoostCampaign::when(!$isValidator, fn($q) => $q->where('user_id', $user->id))
            ->when($allowedPostIds !== null, fn($q) => $q->whereIn('post_id', $allowedPostIds));
        $counts = [
            'all'        => $base()->count(),
            'draft'      => $base()->where('execution_status', 'draft')->count(),
            'pending_n1' => $base()->where('execution_status', 'pending_n1')->count(),
            'pending_n2' => $base()->where('execution_status', 'pending_n2')->count(),
            'approved'   => $base()->where('execution_status', 'approved')->count(),
            'done'       => $base()->whereIn('execution_status', ['done', 'paused_ready', 'active'])->count(),
            'error'      => $base()->whereIn('execution_status', ['error','rejected'])->count(),
        ];

        return view('campaigns.index', compact('campaigns', 'counts', 'isValidator'));
    }

    public function create(Request $request)
    {
        $postId = $request->get('post_id');

        // Charger le post depuis posts_master si post_id fourni
        $post    = null;
        $pageIds = auth()->user()->scopedFacebookPageIds();

        if ($postId) {
            $post = FacebookPost::with('page')->where('post_id', $postId)->first();

            // Vérifier que l'utilisateur a accès à la page de ce post
            if ($post && $pageIds !== null && !in_array($post->facebook_page_id, $pageIds)) {
                abort(403, 'Vous n\'avez pas accès à la page de ce post.');
            }
        }

        // Si pas de post trouvé, lister les posts boostables filtrés par pages assignées
        $posts = null;
        if (!$post) {
            $pageIds = auth()->user()->scopedFacebookPageIds();
            $posts = FacebookPost::with('page')
                ->where('is_boostable', 1)
                ->when($pageIds !== null, fn($q) => $q->whereIn('facebook_page_id', $pageIds))
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

        // Vérification d'accès page avant création
        $postForCheck = FacebookPost::where('post_id', $request->post_id)->first();
        $pageIdsCheck = auth()->user()->scopedFacebookPageIds();
        if ($postForCheck && $pageIdsCheck !== null && !in_array($postForCheck->facebook_page_id, $pageIdsCheck)) {
            abort(403, 'Vous n\'avez pas accès à la page de ce post.');
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
            'execution_status'      => 'draft',
        ]);

        return redirect()->route('campaigns.show', $campaign->id)
            ->with('success', 'Campagne enregistrée ! Cliquez sur "Booster" pour la lancer.');
    }

    // ── Soumettre pour validation N+1 (draft/rejected → pending_n1) ─
    public function submit(BoostCampaign $campaign)
    {
        // Seul le propriétaire peut soumettre (sauf admin)
        if (!auth()->user()->hasRole('admin') && $campaign->user_id !== auth()->id()) {
            abort(403, 'Vous ne pouvez pas soumettre une campagne qui ne vous appartient pas.');
        }

        if (!in_array($campaign->execution_status, ['draft', 'rejected'])) {
            return back()->with('error', 'Seules les campagnes en brouillon ou rejetées peuvent être soumises.');
        }

        $campaign->update([
            'execution_status' => 'pending_n1',
            'error_message'    => null,
        ]);

        $this->notifyCampaignN1Validators($campaign);

        return redirect()->route('campaigns.show', $campaign->id)
            ->with('success', 'Campagne soumise — les validateurs N+1 ont été notifiés.');
    }

    // ── Approuver (pending_n1 → pending_n2, pending_n2 → approved) ─
    public function approve(BoostCampaign $campaign)
    {
        $user = auth()->user();

        if ($campaign->execution_status === 'pending_n1') {
            if (!$user->hasRole(['validator_n1', 'validator', 'admin'])) {
                return back()->with('error', 'Vous n\'avez pas le rôle N+1 requis.');
            }
            if ($campaign->user_id === $user->id && !$user->hasRole('admin')) {
                return back()->with('error', 'Vous ne pouvez pas valider votre propre campagne.');
            }
            $campaign->update(['execution_status' => 'pending_n2', 'error_message' => null]);
            $this->notifyCampaignN2Validators($campaign);
            return redirect()->route('campaigns.show', $campaign->id)
                ->with('success', 'Validation N+1 accordée — les validateurs N+2 ont été notifiés.');
        }

        if ($campaign->execution_status === 'pending_n2') {
            if (!$user->hasRole(['validator_n2', 'admin'])) {
                return back()->with('error', 'Vous n\'avez pas le rôle N+2 requis.');
            }
            $campaign->update(['execution_status' => 'approved', 'error_message' => null]);
            return redirect()->route('campaigns.show', $campaign->id)
                ->with('success', 'Validation N+2 accordée — campagne approuvée, prête à booster.');
        }

        return back()->with('error', 'Cette campagne n\'est pas en attente de validation.');
    }

    // ── Rejeter (pending_n1 ou pending_n2 → rejected) ─────────────
    public function reject(BoostCampaign $campaign)
    {
        $user = auth()->user();

        if ($campaign->execution_status === 'pending_n1') {
            if (!$user->hasRole(['validator_n1', 'validator', 'admin'])) {
                return back()->with('error', 'Vous n\'avez pas le rôle N+1 requis.');
            }
        } elseif ($campaign->execution_status === 'pending_n2') {
            if (!$user->hasRole(['validator_n2', 'admin'])) {
                return back()->with('error', 'Vous n\'avez pas le rôle N+2 requis.');
            }
        } else {
            return back()->with('error', 'Cette campagne n\'est pas en attente de validation.');
        }

        request()->validate(['reason' => 'required|string|max:500']);

        $campaign->update([
            'execution_status' => 'rejected',
            'error_message'    => request('reason'),
        ]);

        return redirect()->route('campaigns.show', $campaign->id)
            ->with('success', 'Campagne rejetée. L\'opérateur peut la corriger et re-soumettre.');
    }

    // ── Activer sur Meta (paused_ready → active) ─────────────────
    public function activate(BoostCampaign $campaign)
    {
        $user = auth()->user();

        if (!$user->hasRole('admin') && $campaign->user_id !== $user->id) {
            abort(403, 'Vous ne pouvez pas activer une campagne qui ne vous appartient pas.');
        }

        if ($campaign->execution_status !== 'paused_ready') {
            return back()->with('error', 'Seules les campagnes en statut "Créée (PAUSED)" peuvent être activées.');
        }

        // Récupérer le token Meta via le post → page
        $post  = FacebookPost::where('post_id', $campaign->post_id)->first();
        $page  = $post ? FacebookPage::find($post->facebook_page_id) : null;
        $token = $page?->access_token;

        if (!$token) {
            return back()->with('error', 'Token Meta introuvable pour cette page. Vérifiez la configuration des pages Facebook.');
        }

        try {
            $errors = [];

            foreach ([
                'Campaign' => $campaign->meta_campaign_id,
                'AdSet'    => $campaign->meta_adset_id,
                'Ad'       => $campaign->meta_ad_id,
            ] as $label => $metaId) {
                if (!$metaId) continue;
                $resp = Http::asForm()
                    ->post("https://graph.facebook.com/v23.0/{$metaId}", [
                        'status'       => 'ACTIVE',
                        'access_token' => $token,
                    ]);
                if ($resp->failed() || isset($resp->json()['error'])) {
                    $errors[] = "{$label}: " . ($resp->json()['error']['message'] ?? $resp->body());
                }
            }

            if (!empty($errors)) {
                $campaign->update([
                    'execution_status' => 'error',
                    'error_message'    => 'Échec activation : ' . implode(' | ', $errors),
                ]);
                return redirect()->route('campaigns.show', $campaign->id)
                    ->with('error', 'Erreur lors de l\'activation sur Meta : ' . implode(' | ', $errors));
            }

            $campaign->update([
                'execution_status' => 'active',
                'error_message'    => null,
            ]);

            return redirect()->route('campaigns.show', $campaign->id)
                ->with('success', 'Campagne activée sur Meta Ads ! Elle est maintenant en diffusion.');

        } catch (\Throwable $e) {
            Log::error('CampaignController::activate', ['error' => $e->getMessage()]);
            $campaign->update([
                'execution_status' => 'error',
                'error_message'    => $e->getMessage(),
            ]);
            return redirect()->route('campaigns.show', $campaign->id)
                ->with('error', 'Erreur inattendue lors de l\'activation : ' . $e->getMessage());
        }
    }

    // ── Lancer le boost (approved → triggerN8n) ───────────────────
    public function launch(BoostCampaign $campaign)
    {
        $user    = auth()->user();
        $isAdmin = $user->hasRole('admin');

        if (!$isAdmin && $campaign->execution_status !== 'approved') {
            return back()->with('error', 'La campagne doit être approuvée (N+1 + N+2) avant d\'être boostée.');
        }
        if ($isAdmin && !in_array($campaign->execution_status, ['draft','approved','error'])) {
            return back()->with('error', 'Cette campagne ne peut pas être lancée dans son état actuel.');
        }

        $this->triggerN8n($campaign);

        return redirect()->route('campaigns.show', $campaign->id)
            ->with('success', 'Boost lancé ! n8n va créer la campagne sur Meta Ads.');
    }

    // ── File d'attente validateurs ────────────────────────────────
    public function pendingList()
    {
        $user    = auth()->user();
        $pageIds = $user->scopedFacebookPageIds();
        $allowedPostIds = $pageIds !== null
            ? FacebookPost::whereIn('facebook_page_id', $pageIds)->pluck('post_id')
            : null;

        if ($user->hasRole('admin')) {
            $campaigns = BoostCampaign::with('user')
                ->whereIn('execution_status', ['pending_n1', 'pending_n2'])
                ->when($allowedPostIds !== null, fn($q) => $q->whereIn('post_id', $allowedPostIds))
                ->latest()->paginate(20);
        } elseif ($user->hasRole('validator_n2')) {
            $campaigns = BoostCampaign::with('user')
                ->where('execution_status', 'pending_n2')
                ->when($allowedPostIds !== null, fn($q) => $q->whereIn('post_id', $allowedPostIds))
                ->latest()->paginate(20);
        } else {
            // validator_n1 / validator
            $campaigns = BoostCampaign::with('user')
                ->where('execution_status', 'pending_n1')
                ->when($allowedPostIds !== null, fn($q) => $q->whereIn('post_id', $allowedPostIds))
                ->latest()->paginate(20);
        }

        $pendingN1Count = BoostCampaign::where('execution_status', 'pending_n1')
            ->when($allowedPostIds !== null, fn($q) => $q->whereIn('post_id', $allowedPostIds))
            ->count();
        $pendingN2Count = BoostCampaign::where('execution_status', 'pending_n2')
            ->when($allowedPostIds !== null, fn($q) => $q->whereIn('post_id', $allowedPostIds))
            ->count();

        return view('campaigns.pending', compact('campaigns', 'pendingN1Count', 'pendingN2Count'));
    }

    public function show(BoostCampaign $campaign)
    {
        return view('campaigns.show', compact('campaign'));
    }

    // ── Callback n8n → Laravel (UPDATE execution_status + meta IDs) ──

    public function n8nCallback(Request $request)
    {
        // Vérification secret (timing-safe)
        $secret   = SettingService::get('n8n.secret');
        $received = $request->header('X-N8N-Secret', '');
        if ($secret && !hash_equals($secret, $received)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $campaign = BoostCampaign::find($request->input('campaign_db_id'));
        if (!$campaign) {
            return response()->json(['error' => 'Campaign not found'], 404);
        }

        // N8N envoie 'done' → campagne créée sur Meta en PAUSED, en attente d'activation
        $status = $request->input('execution_status', 'done');
        if ($status === 'done') $status = 'paused_ready';

        $campaign->update([
            'execution_status' => $status,
            'meta_campaign_id' => $request->input('meta_campaign_id'),
            'meta_adset_id'    => $request->input('meta_adset_id'),
            'meta_ad_id'       => $request->input('meta_ad_id'),
            'error_message'    => $request->input('error_message'),
            'launched_at'      => $status === 'paused_ready' ? now() : null,
        ]);

        return response()->json(['success' => true]);
    }

    // ── Notifications validateurs (filtrées par page) ──────────

    private function notifyCampaignN1Validators(BoostCampaign $campaign): void
    {
        $pageId  = FacebookPost::where('post_id', $campaign->post_id)->value('facebook_page_id');
        $n1Users = User::role(['validator_n1', 'validator'])
            ->when($pageId, fn($q) => $q->whereHas('facebookPages', fn($q2) => $q2->where('facebook_pages.id', $pageId)))
            ->get();

        $n1Users->each(fn($u) => $u->notify(new CampaignSubmittedNotification($campaign)));
        User::role('admin')->get()->each(fn($a) => $a->notify(new CampaignSubmittedNotification($campaign)));
    }

    private function notifyCampaignN2Validators(BoostCampaign $campaign): void
    {
        $pageId  = FacebookPost::where('post_id', $campaign->post_id)->value('facebook_page_id');
        $n2Users = User::role('validator_n2')
            ->when($pageId, fn($q) => $q->whereHas('facebookPages', fn($q2) => $q2->where('facebook_pages.id', $pageId)))
            ->get();

        $n2Users->each(fn($u) => $u->notify(new CampaignPendingN2Notification($campaign)));
        User::role('admin')->get()->each(fn($a) => $a->notify(new CampaignPendingN2Notification($campaign)));
    }

    // ── Envoi vers n8n ──

    private function triggerN8n(BoostCampaign $campaign): void
    {
        $mockMode   = SettingService::bool('n8n.mock_mode', true);
        $webhookUrl = SettingService::get('n8n.webhook_campaign');

        if ($mockMode || !$webhookUrl) {
            // Simulation : marquer done immédiatement
            $campaign->update([
                'execution_status' => 'paused_ready',
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
                ->post($webhookUrl, [
                    'campaign_db_id'  => $campaign->id,
                    'callback_secret' => $secret,
                ]);

        } catch (\Throwable $e) {
            Log::error('CampaignController::triggerN8n', ['error' => $e->getMessage()]);
            $campaign->update([
                'execution_status' => 'error',
                'error_message'    => $e->getMessage(),
            ]);
        }
    }
}
