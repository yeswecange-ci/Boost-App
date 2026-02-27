<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\BoostRequest;
use App\Models\User;
use App\Notifications\BoostApprovedNotification;
use App\Notifications\BoostRejectedNotification;
use App\Notifications\BoostActivatedNotification;
use App\Notifications\BoostPendingN2Notification;
use App\Services\N8nWebhookService;
use Illuminate\Http\Request;

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
        $boosts = BoostRequest::with('operator')
                              ->where('status', 'pending_n1')
                              ->latest()
                              ->paginate(10);

        return view('boost.pending-n1', compact('boosts'));
    }

    /**
     * File N+2 — demandes validées N+1, sensibilité moyenne/élevée
     */
    public function pendingN2()
    {
        $boosts = BoostRequest::with('operator', 'approvals.user')
                              ->where('status', 'pending_n2')
                              ->latest()
                              ->paginate(10);

        return view('boost.pending-n2', compact('boosts'));
    }

    /**
     * Historique complet
     */
    public function all(Request $request)
    {
        $query = BoostRequest::with('operator');

        if ($request->status === 'rejected') {
            $query->whereIn('status', ['rejected_n1', 'rejected_n2']);
        } elseif ($request->status === 'pending') {
            $query->whereIn('status', ['pending_n1', 'pending_n2']);
        } elseif ($request->status) {
            $query->where('status', $request->status);
        }

        $boosts = $query->latest()->paginate(15);

        return view('boost.all', compact('boosts'));
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

        // Enregistrer la décision dans l'historique
        Approval::create([
            'boost_request_id' => $boost->id,
            'level'            => 'N1',
            'action'           => 'approved',
            'comment'          => $request->comment,
            'user_id'          => auth()->id(),
        ]);

        $boost->update(['validator_id' => auth()->id()]);

        // Routage selon la sensibilité
        if ($boost->needsN2()) {
            // Sensibilité moyenne ou élevée → escalader vers N+2
            $boost->update(['status' => 'pending_n2']);

            // Notifier les validateurs N+2
            $n2Users = User::role('validator_n2')->get();
            foreach ($n2Users as $user) {
                $user->notify(new BoostPendingN2Notification($boost));
            }
            $admins = User::role('admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new BoostPendingN2Notification($boost));
            }

            return redirect()->back()->with('success',
                "Boost #" . $boost->id . " validé N+1. Escaladé vers N+2 (sensibilité : {$boost->sensitivity})."
            );
        }

        // Sensibilité faible → approbation finale + déclencher N8N
        $boost->update(['status' => 'approved']);
        $boost->operator->notify(new BoostApprovedNotification($boost));

        try {
            $this->n8n->triggerCreate($boost);
            $message = "Boost #" . $boost->id . " approuvé N+1. " .
                (config('services.n8n.mock_mode') ? "Campagne créée (mode mock)." : "N8N crée la campagne Meta.");
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

        $boost->operator->notify(new BoostRejectedNotification($boost));

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

        $boost->operator->notify(new BoostRejectedNotification($boost));

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

        $boost->operator->notify(new BoostApprovedNotification($boost));

        try {
            $this->n8n->triggerCreate($boost);
            $message = "Boost #" . $boost->id . " approuvé N+2. " .
                (config('services.n8n.mock_mode') ? "Campagne créée (mode mock)." : "N8N crée la campagne Meta.");
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

        $boost->operator->notify(new BoostRejectedNotification($boost));

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

        $boost->operator->notify(new BoostRejectedNotification($boost));

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

        $boost->operator->notify(new BoostRejectedNotification($boost));

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
            $message = config('services.n8n.mock_mode')
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
            $message = config('services.n8n.mock_mode')
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
}
