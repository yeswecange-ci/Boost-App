<?php

namespace App\Services;

use App\Models\BoostRequest;
use App\Services\SettingService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class N8nWebhookService
{
    private bool $mockMode;
    private int  $timeout;

    public function __construct()
    {
        // Priorité : DB → config → défaut
        $this->mockMode = SettingService::bool('n8n.mock_mode', true);
        $this->timeout  = SettingService::int('n8n.timeout', 10);
    }

    /**
     * Étape 4 — Déclenche la création de la campagne Meta via N8N.
     * Appelé juste après l'approbation d'un boost.
     * Statut attendu après appel : 'creating'
     *
     * @throws \RuntimeException si N8N est injoignable et mock_mode=false
     */
    public function triggerCreate(BoostRequest $boost): void
    {
        $payload = $this->buildCreatePayload($boost);

        $boost->update([
            'status'      => 'creating',
            'n8n_payload' => $payload,
        ]);

        if ($this->mockMode) {
            $this->simulateCreateCallback($boost);
            return;
        }

        $webhookUrl = SettingService::get('n8n.webhook_create');

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($webhookUrl, $payload);

            if ($response->failed()) {
                Log::error('N8N webhook create failed', [
                    'boost_id' => $boost->id,
                    'status'   => $response->status(),
                    'body'     => $response->body(),
                ]);
                // Revert to approved so validator can retry
                $boost->update(['status' => 'approved', 'n8n_payload' => null]);
                throw new \RuntimeException('N8N a retourné une erreur : ' . $response->status());
            }

            Log::info('N8N webhook create sent', ['boost_id' => $boost->id]);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('N8N connection error', ['boost_id' => $boost->id, 'error' => $e->getMessage()]);
            $boost->update(['status' => 'approved', 'n8n_payload' => null]);
            throw new \RuntimeException('Impossible de joindre N8N. Vérifiez la configuration.');
        }
    }

    /**
     * Étape 5a — Déclenche l'activation d'une campagne Meta en pause.
     * Statut attendu après appel : 'active'
     *
     * @throws \RuntimeException si N8N est injoignable
     */
    public function triggerActivate(BoostRequest $boost): void
    {
        $payload = $this->buildActionPayload($boost, 'activate');

        if ($this->mockMode) {
            $boost->update([
                'status'       => 'active',
                'n8n_response' => array_merge($boost->n8n_response ?? [], [
                    'activated_at' => now()->toIso8601String(),
                    'mock'         => true,
                ]),
            ]);
            Log::info('[MOCK] N8N activate simulated', ['boost_id' => $boost->id]);
            return;
        }

        $webhookUrl = SettingService::get('n8n.webhook_activate');

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($webhookUrl, $payload);

            if ($response->failed()) {
                throw new \RuntimeException('N8N activate a retourné une erreur : ' . $response->status());
            }

            // N8N activera et appellera le callback — statut sera mis à jour là-bas
            Log::info('N8N webhook activate sent', ['boost_id' => $boost->id]);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new \RuntimeException('Impossible de joindre N8N pour l\'activation.');
        }
    }

    /**
     * Étape 5b — Met en pause une campagne active.
     *
     * @throws \RuntimeException si N8N est injoignable
     */
    public function triggerPause(BoostRequest $boost): void
    {
        $payload = $this->buildActionPayload($boost, 'pause');

        if ($this->mockMode) {
            $boost->update([
                'status'       => 'paused',
                'n8n_response' => array_merge($boost->n8n_response ?? [], [
                    'paused_at' => now()->toIso8601String(),
                    'mock'      => true,
                ]),
            ]);
            Log::info('[MOCK] N8N pause simulated', ['boost_id' => $boost->id]);
            return;
        }

        $webhookUrl = SettingService::get('n8n.webhook_pause');

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($webhookUrl, $payload);

            if ($response->failed()) {
                throw new \RuntimeException('N8N pause a retourné une erreur : ' . $response->status());
            }

            Log::info('N8N webhook pause sent', ['boost_id' => $boost->id]);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new \RuntimeException('Impossible de joindre N8N pour la mise en pause.');
        }
    }

    // ─────────────────────────────────────────────────────────
    // Payloads
    // ─────────────────────────────────────────────────────────

    /**
     * Payload complet envoyé à N8N pour la création de campagne.
     * N8N a besoin de tout pour créer Campaign + AdSet + Ad.
     */
    private function buildCreatePayload(BoostRequest $boost): array
    {
        return [
            // Identification
            'boost_id'     => $boost->id,
            'callback_url' => route('webhook.n8n.boost-created'),

            // Post Facebook à booster
            'post_id'   => $boost->post_id,
            'page_id'   => $boost->page_id,
            'page_name' => $boost->page_name,
            'post_url'  => $boost->post_url,

            // Paramètres de campagne
            'start_date' => $boost->start_date->format('Y-m-d'),
            'end_date'   => $boost->end_date->format('Y-m-d'),
            'budget'     => (float) $boost->budget,
            'currency'   => $boost->currency,

            // Audience
            'target' => [
                'age_min'   => (int) $boost->target['age_min'],
                'age_max'   => (int) $boost->target['age_max'],
                'gender'    => $boost->target['gender'],
                'countries' => $boost->target['countries'],
                'interests' => $boost->target['interests'] ?? [],
            ],

            // WhatsApp CTA
            'whatsapp_url' => $boost->whatsapp_url,

            // Opérateur
            'operator_name'  => $boost->operator->name,
            'operator_email' => $boost->operator->email,
        ];
    }

    /**
     * Payload pour activate/pause — N8N a juste besoin des IDs Meta.
     */
    private function buildActionPayload(BoostRequest $boost, string $action): array
    {
        return [
            'boost_id'          => $boost->id,
            'action'            => $action,
            'meta_campaign_id'  => $boost->meta_campaign_id,
            'meta_adset_id'     => $boost->meta_adset_id,
            'meta_ad_id'        => $boost->meta_ad_id,
            'callback_url'      => route('webhook.n8n.boost-activated'),
        ];
    }

    // ─────────────────────────────────────────────────────────
    // Mock — simule le callback N8N en mode développement
    // ─────────────────────────────────────────────────────────

    private function simulateCreateCallback(BoostRequest $boost): void
    {
        $mockCampaignId = 'MOCK_CAMPAIGN_' . $boost->id . '_' . now()->timestamp;
        $mockAdsetId    = 'MOCK_ADSET_'    . $boost->id . '_' . now()->timestamp;
        $mockAdId       = 'MOCK_AD_'       . $boost->id . '_' . now()->timestamp;

        $boost->update([
            'status'            => 'paused_ready',
            'meta_campaign_id'  => $mockCampaignId,
            'meta_adset_id'     => $mockAdsetId,
            'meta_ad_id'        => $mockAdId,
            'n8n_response'      => [
                'mock'          => true,
                'created_at'    => now()->toIso8601String(),
                'campaign_id'   => $mockCampaignId,
                'adset_id'      => $mockAdsetId,
                'ad_id'         => $mockAdId,
                'message'       => 'Campagne créée en mode mock — prête pour activation',
            ],
        ]);

        Log::info('[MOCK] N8N create simulated', [
            'boost_id'    => $boost->id,
            'campaign_id' => $mockCampaignId,
        ]);
    }
}
