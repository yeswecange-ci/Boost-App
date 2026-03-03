<?php

namespace App\Http\Controllers;

use App\Models\AdsEntity;
use App\Models\BoostRequest;
use App\Models\BoostRun;
use App\Models\User;
use App\Notifications\BoostCreatedNotification;
use App\Notifications\BoostActivatedNotification;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Reçoit le callback de N8N après création de la campagne Meta.
     *
     * Payload attendu de N8N :
     * {
     *   "boost_id": 1,
     *   "meta_campaign_id": "...",
     *   "meta_adset_id": "...",
     *   "meta_ad_id": "...",
     *   "error": null          // ou message d'erreur
     * }
     *
     * Route : POST /webhook/n8n/boost-created
     * Protégée par secret dans le header X-N8N-Secret
     */
    public function boostCreated(Request $request)
    {
        if (!$this->validateSecret($request)) {
            Log::warning('N8N webhook: secret invalide', ['ip' => $request->ip()]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'boost_id'         => 'required|integer|exists:boost_requests,id',
            'meta_campaign_id' => 'nullable|string',
            'meta_adset_id'    => 'nullable|string',
            'meta_ad_id'       => 'nullable|string',
            'error'            => 'nullable|string',
        ]);

        $boost = BoostRequest::with('operator')->findOrFail($data['boost_id']);

        Log::info('N8N callback boost-created reçu', [
            'boost_id' => $boost->id,
            'payload'  => $data,
        ]);

        // Cas erreur N8N
        if (!empty($data['error'])) {
            $boost->update([
                'status'       => 'failed',
                'n8n_response' => ['error' => $data['error'], 'received_at' => now()->toIso8601String()],
            ]);
            Log::error('N8N signale une erreur de création', ['boost_id' => $boost->id, 'error' => $data['error']]);
            return response()->json(['received' => true]);
        }

        // Succès — mise à jour avec les IDs Meta
        $boost->update([
            'status'           => 'paused_ready',
            'meta_campaign_id' => $data['meta_campaign_id'],
            'meta_adset_id'    => $data['meta_adset_id'],
            'meta_ad_id'       => $data['meta_ad_id'],
            'n8n_response'     => array_merge($boost->n8n_response ?? [], [
                'created_at'  => now()->toIso8601String(),
                'campaign_id' => $data['meta_campaign_id'],
                'adset_id'    => $data['meta_adset_id'],
                'ad_id'       => $data['meta_ad_id'],
            ]),
        ]);

        // Créer / mettre à jour l'AdsEntity pour traçabilité (idempotence)
        $boostRun = BoostRun::where('boost_request_id', $boost->id)->latest()->first();
        if ($boostRun) {
            AdsEntity::updateOrCreate(
                ['boost_run_id' => $boostRun->id],
                [
                    'campaign_id'     => $data['meta_campaign_id'],
                    'adset_id'        => $data['meta_adset_id'],
                    'ad_id'           => $data['meta_ad_id'],
                    'campaign_status' => 'PAUSED',
                    'adset_status'    => 'PAUSED',
                    'ad_status'       => 'PAUSED',
                    'payload'         => [
                        'received_at'    => now()->toIso8601String(),
                        'object_story_id'=> $boost->page_id . '_' . $boost->post_id,
                    ],
                ]
            );
            $boostRun->update(['status' => 'PAUSED']);
        }

        // Notifier l'opérateur + les validateurs que la campagne est prête à activer
        $boost->operator?->notify(new BoostCreatedNotification($boost));

        $validators = User::role(['validator_n1', 'validator_n2', 'validator', 'admin'])->get();
        foreach ($validators as $validator) {
            $validator->notify(new BoostCreatedNotification($boost));
        }

        return response()->json(['received' => true, 'status' => 'paused_ready']);
    }

    /**
     * Reçoit le callback de N8N après activation de la campagne.
     *
     * Payload attendu :
     * {
     *   "boost_id": 1,
     *   "status": "active",   // ou "paused"
     *   "error": null
     * }
     *
     * Route : POST /webhook/n8n/boost-activated
     */
    public function boostActivated(Request $request)
    {
        if (!$this->validateSecret($request)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'boost_id' => 'required|integer|exists:boost_requests,id',
            'status'   => 'required|in:active,paused,failed',
            'error'    => 'nullable|string',
        ]);

        $boost = BoostRequest::with('operator')->findOrFail($data['boost_id']);

        // Idempotence : seuls les boosts en état transitoire acceptent la mise à jour.
        // Les statuts finaux (completed, cancelled, failed déjà enregistré) sont ignorés.
        $allowedStatuses = ['creating', 'paused_ready', 'active', 'paused', 'approved'];
        if (!in_array($boost->status, $allowedStatuses)) {
            Log::warning('N8N boost-activated ignoré — statut final déjà atteint', [
                'boost_id'       => $boost->id,
                'current_status' => $boost->status,
                'requested'      => $data['status'],
            ]);
            return response()->json(['received' => true, 'ignored' => true, 'reason' => 'status_already_final']);
        }

        $boost->update([
            'status'       => $data['status'],
            'n8n_response' => array_merge($boost->n8n_response ?? [], [
                'activated_at' => now()->toIso8601String(),
                'final_status' => $data['status'],
            ]),
        ]);

        if ($data['status'] === 'active') {
            $boost->operator?->notify(new BoostActivatedNotification($boost));
        }

        Log::info('N8N callback boost-activated', ['boost_id' => $boost->id, 'status' => $data['status']]);

        return response()->json(['received' => true, 'status' => $data['status']]);
    }

    // ─────────────────────────────────────────────────────────

    /**
     * Vérifie le secret partagé avec N8N.
     * N8N doit envoyer le header : X-N8N-Secret: {N8N_WEBHOOK_SECRET}
     */
    private function validateSecret(Request $request): bool
    {
        // Priorité : valeur en DB (settings UI) → fallback config/.env
        $expected = SettingService::get('n8n.secret') ?: config('services.n8n.secret');
        $received = $request->header('X-N8N-Secret');

        if (!$expected || !$received) {
            return false;
        }

        return hash_equals($expected, $received);
    }
}
