<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\BoostCampaign;
use App\Models\BoostRequest;
use App\Models\FacebookPage;
use App\Models\FacebookPost;
use App\Models\User;
use App\Notifications\BoostApprovedNotification;
use App\Notifications\BoostRejectedNotification;
use App\Notifications\BoostChangesRequestedNotification;
use App\Notifications\BoostCancelledNotification;
use App\Notifications\BoostActivatedNotification;
use App\Notifications\BoostPendingN2Notification;
use App\Services\N8nWebhookService;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ValidatorController extends Controller
{
    public function __construct(private N8nWebhookService $n8n) {}

    // ─────────────────────────────────────────────────────────
    // Files d'attente de validation
    // ─────────────────────────────────────────────────────────

    /**
     * File N+1 — demandes soumises par l'opérateur
     */
    public function pendingN1()
    {
        $fbPageIds = $this->scopedFbPageStringIds();

        $boosts = BoostRequest::with('operator')
                              ->where('status', 'pending_n1')
                              ->when($fbPageIds !== null, fn($q) => $q->whereIn('page_id', $fbPageIds))
                              ->latest()
                              ->paginate(10);

        return view('boost.pending-n1', compact('boosts'));
    }

    /**
     * File N+2 — demandes validées N+1, sensibilité moyenne/élevée
     */
    public function pendingN2()
    {
        $fbPageIds = $this->scopedFbPageStringIds();

        $boosts = BoostRequest::with('operator', 'approvals.user')
                              ->where('status', 'pending_n2')
                              ->when($fbPageIds !== null, fn($q) => $q->whereIn('page_id', $fbPageIds))
                              ->latest()
                              ->paginate(10);

        return view('boost.pending-n2', compact('boosts'));
    }

    /**
     * Historique complet des campagnes Media Buyer
     */
    public function all(Request $request)
    {
        $pageIds        = auth()->user()->scopedFacebookPageIds();
        $allowedPostIds = $pageIds !== null
            ? FacebookPost::whereIn('facebook_page_id', $pageIds)->pluck('post_id')
            : null;

        $query = BoostCampaign::with('user')
            ->when($allowedPostIds !== null, fn($q) => $q->whereIn('post_id', $allowedPostIds));

        if ($request->status === 'pending') {
            $query->whereIn('execution_status', ['pending_n1', 'pending_n2']);
        } elseif ($request->status === 'error') {
            $query->whereIn('execution_status', ['error', 'rejected']);
        } elseif ($request->status) {
            $query->where('execution_status', $request->status);
        }

        $campaigns = $query->latest()->paginate(15)->withQueryString();

        return view('boost.all', compact('campaigns'));
    }

    // ─────────────────────────────────────────────────────────
    // Actions N+1
    // ─────────────────────────────────────────────────────────

    /**
     * Validation N+1 : Approuver
     * - Sensibilité faible → APPROVED + trigger N8N
     * - Sensibilité moyenne/élevée → PENDING_N2 + notifier N+2
     */
    public function approveN1(Request $request, BoostRequest $boost)
    {
        abort_if($boost->status !== 'pending_n1', 422, 'Ce boost ne peut pas être validé N+1.');

        $request->validate([
            'comment' => 'nullable|string|max:500',
        ]);

        // Transaction : approval + mise à jour statut atomiques
        DB::transaction(function () use ($boost, $request) {
            Approval::create([
                'boost_request_id' => $boost->id,
                'level'            => 'N1',
                'action'           => 'approved',
                'comment'          => $request->comment,
                'user_id'          => auth()->id(),
            ]);

            $boost->update(['validator_id' => auth()->id()]);

            if ($boost->needsN2()) {
                $boost->update(['status' => 'pending_n2']);
            } else {
                $boost->update(['status' => 'approved']);
            }
        });

        // Notifications et N8N en dehors de la transaction
        if ($boost->needsN2()) {
            // Notifier uniquement les N+2 assignés à la page concernée
            $boostFbPageDbId = FacebookPage::where('page_id', $boost->page_id)->value('id');
            $n2Users = User::role('validator_n2')
                ->when($boostFbPageDbId, fn($q) => $q->whereHas('facebookPages', fn($q2) => $q2->where('facebook_pages.id', $boostFbPageDbId)))
                ->get();
            foreach ($n2Users as $user) {
                $user->notify(new BoostPendingN2Notification($boost));
            }
            User::role('admin')->get()->each(fn($a) => $a->notify(new BoostPendingN2Notification($boost)));

            return redirect()->back()->with('success',
                "Boost #" . $boost->id . " validé N+1. Escaladé vers N+2 (sensibilité : {$boost->sensitivity})."
            );
        }

        // Sensibilité faible → approbation finale + déclencher N8N
        $boost->operator?->notify(new BoostApprovedNotification($boost));

        try {
            $this->n8n->triggerCreate($boost);
            $message = "Boost #" . $boost->id . " approuvé N+1. " .
                (SettingService::bool('n8n.mock_mode') ? "Campagne créée (mode mock)." : "N8N crée la campagne Meta.");
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error',
                "Boost approuvé mais N8N injoignable : " . $e->getMessage() . " — Relancez via la fiche."
            );
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Validation N+1 : Demander modification → repasse en DRAFT
     */
    public function requestChangesN1(Request $request, BoostRequest $boost)
    {
        abort_if($boost->status !== 'pending_n1', 422, 'Ce boost ne peut pas faire l\'objet d\'une demande de modification N+1.');

        $request->validate([
            'comment' => 'required|string|min:10|max:500',
        ]);

        DB::transaction(function () use ($boost, $request) {
            Approval::create([
                'boost_request_id' => $boost->id,
                'level'            => 'N1',
                'action'           => 'changes_requested',
                'comment'          => $request->comment,
                'user_id'          => auth()->id(),
            ]);

            $boost->update([
                'status'           => 'draft',
                'rejection_reason' => $request->comment,
            ]);
        });


        $boost->operator?->notify(new BoostChangesRequestedNotification($boost));

        return redirect()->back()->with('success',
            "Boost #{$boost->id} renvoyé à l'opérateur pour modification."
        );
    }

    /**
     * Validation N+1 : Rejeter
     */
    public function rejectN1(Request $request, BoostRequest $boost)
    {
        abort_if($boost->status !== 'pending_n1', 422, 'Ce boost ne peut pas être rejeté N+1.');

        $request->validate([
            'rejection_reason' => 'required|string|min:10|max:500',
        ]);

        DB::transaction(function () use ($boost, $request) {
            Approval::create([
                'boost_request_id' => $boost->id,
                'level'            => 'N1',
                'action'           => 'rejected',
                'comment'          => $request->rejection_reason,
                'user_id'          => auth()->id(),
            ]);

            $boost->update([
                'status'           => 'rejected_n1',
                'validator_id'     => auth()->id(),
                'rejection_reason' => $request->rejection_reason,
            ]);
        });


        $boost->operator?->notify(new BoostRejectedNotification($boost));

        return redirect()->back()->with('success', "Boost #" . $boost->id . " rejeté N+1.");
    }

    // ─────────────────────────────────────────────────────────
    // Actions N+2
    // ─────────────────────────────────────────────────────────

    /**
     * Validation N+2 : Approuver → APPROVED + trigger N8N
     */
    public function approveN2(Request $request, BoostRequest $boost)
    {
        abort_if($boost->status !== 'pending_n2', 422, 'Ce boost ne peut pas être validé N+2.');

        $request->validate([
            'comment' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($boost, $request) {
            Approval::create([
                'boost_request_id' => $boost->id,
                'level'            => 'N2',
                'action'           => 'approved',
                'comment'          => $request->comment,
                'user_id'          => auth()->id(),
            ]);

            $boost->update([
                'status'       => 'approved',
                'validator_id' => auth()->id(),
            ]);
        });


        $boost->operator?->notify(new BoostApprovedNotification($boost));

        try {
            $this->n8n->triggerCreate($boost);
            $message = "Boost #" . $boost->id . " approuvé N+2. " .
                (SettingService::bool('n8n.mock_mode') ? "Campagne créée (mode mock)." : "N8N crée la campagne Meta.");
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error',
                "Boost approuvé mais N8N injoignable : " . $e->getMessage() . " — Relancez via la fiche."
            );
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Validation N+2 : Rejeter
     */
    public function rejectN2(Request $request, BoostRequest $boost)
    {
        abort_if($boost->status !== 'pending_n2', 422, 'Ce boost ne peut pas être rejeté N+2.');

        $request->validate([
            'rejection_reason' => 'required|string|min:10|max:500',
        ]);

        DB::transaction(function () use ($boost, $request) {
            Approval::create([
                'boost_request_id' => $boost->id,
                'level'            => 'N2',
                'action'           => 'rejected',
                'comment'          => $request->rejection_reason,
                'user_id'          => auth()->id(),
            ]);

            $boost->update([
                'status'           => 'rejected_n2',
                'validator_id'     => auth()->id(),
                'rejection_reason' => $request->rejection_reason,
            ]);
        });


        $boost->operator?->notify(new BoostRejectedNotification($boost));

        return redirect()->back()->with('success', "Boost #" . $boost->id . " rejeté N+2.");
    }

    /**
     * Validation N+2 : Demander modification → repasse en DRAFT
     */
    public function requestChangesN2(Request $request, BoostRequest $boost)
    {
        abort_if($boost->status !== 'pending_n2', 422, 'Ce boost ne peut pas faire l\'objet d\'une demande de modification N+2.');

        $request->validate([
            'comment' => 'required|string|min:10|max:500',
        ]);

        DB::transaction(function () use ($boost, $request) {
            Approval::create([
                'boost_request_id' => $boost->id,
                'level'            => 'N2',
                'action'           => 'changes_requested',
                'comment'          => $request->comment,
                'user_id'          => auth()->id(),
            ]);

            $boost->update([
                'status'           => 'draft',
                'rejection_reason' => $request->comment,
            ]);
        });


        $boost->operator?->notify(new BoostChangesRequestedNotification($boost));

        return redirect()->back()->with('success',
            "Boost #{$boost->id} renvoyé à l'opérateur pour modification."
        );
    }

    // ─────────────────────────────────────────────────────────
    // Actions post-N8N
    // ─────────────────────────────────────────────────────────

    /**
     * Annuler une campagne en attente d'activation (paused_ready)
     */
    public function cancel(Request $request, BoostRequest $boost)
    {
        abort_if($boost->status !== 'paused_ready', 422, 'Seules les campagnes prêtes (paused_ready) peuvent être annulées.');

        $request->validate([
            'comment' => 'nullable|string|max:500',
        ]);

        $boost->update([
            'status'           => 'cancelled',
            'rejection_reason' => $request->comment,
        ]);

        $boost->operator?->notify(new BoostCancelledNotification($boost));

        return redirect()->back()->with('success', "Boost #{$boost->id} annulé.");
    }

    /**
     * Activer la campagne Meta (elle est en PAUSE après création N8N)
     */
    public function activate(Request $request, BoostRequest $boost)
    {
        abort_if(!in_array($boost->status, ['paused_ready', 'paused']), 422, 'Seules les campagnes prêtes ou en pause peuvent être activées.');

        try {
            $this->n8n->triggerActivate($boost);
            $message = SettingService::bool('n8n.mock_mode')
                ? "Campagne #" . $boost->id . " activée (mode mock)."
                : "Demande d'activation envoyée à N8N. La campagne sera active dans quelques instants.";
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', "Impossible d'activer : " . $e->getMessage());
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Mettre en pause une campagne active
     */
    public function pause(Request $request, BoostRequest $boost)
    {
        abort_if($boost->status !== 'active', 422, 'Seules les campagnes actives peuvent être mises en pause.');

        try {
            $this->n8n->triggerPause($boost);
            $message = SettingService::bool('n8n.mock_mode')
                ? "Campagne #" . $boost->id . " mise en pause (mode mock)."
                : "Demande de pause envoyée à N8N.";
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', "Impossible de mettre en pause : " . $e->getMessage());
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Relancer N8N pour un boost approuvé mais non créé (N8N était down)
     */
    public function retryN8n(Request $request, BoostRequest $boost)
    {
        abort_if($boost->status !== 'approved', 422, 'Seuls les boosts approuvés peuvent être relancés.');

        try {
            $this->n8n->triggerCreate($boost);
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', "N8N toujours injoignable : " . $e->getMessage());
        }

        return redirect()->back()->with('success', "N8N relancé pour le boost #" . $boost->id . ".");
    }

    /**
     * Retourne les page_id (string Facebook) accessibles à l'utilisateur courant.
     * null = admin = pas de filtre. [] = aucune page assignée.
     */
    private function scopedFbPageStringIds(): ?array
    {
        $user    = auth()->user();
        $pageIds = $user->scopedFacebookPageIds(); // array d'IDs DB ou null
        if ($pageIds === null) return null;
        return FacebookPage::whereIn('id', $pageIds)->pluck('page_id')->toArray();
    }
}
