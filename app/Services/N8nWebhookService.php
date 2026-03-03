<?php

namespace App\Services;

use App\Models\AdsEntity;
use App\Models\BoostRequest;
use App\Models\BoostRun;
use App\Models\FacebookPage;
use App\Models\FacebookPost;
use App\Services\SettingService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class N8nWebhookService
{
    private bool $mockMode;
    private int  $timeout;

    // City key Meta pour Abidjan (PDF Architecture — géo exact)
    const ABIDJAN_CITY_KEY = '102057199141875';

    public function __construct()
    {
        $this->mockMode = SettingService::bool('n8n.mock_mode', false);
        $this->timeout  = SettingService::int('n8n.timeout', 10);
    }

    // ─────────────────────────────────────────────────────────
    // Création campagne Meta via N8N
    // ─────────────────────────────────────────────────────────

    /**
     * @throws \RuntimeException si N8N est injoignable et mock_mode=false
     */
    public function triggerCreate(BoostRequest $boost): void
    {
        // Idempotence : refuser si un BoostRun avec AdsEntity complète existe déjà
        $existingRun = $boost->boostRun()->with('adsEntity')->first();
        if ($existingRun && $existingRun->adsEntity?->isComplete()) {
            Log::warning('N8N triggerCreate ignoré — AdsEntity déjà complète', [
                'boost_id'     => $boost->id,
                'campaign_id'  => $existingRun->adsEntity->campaign_id,
            ]);
            return;
        }

        $payload = $this->buildCreatePayload($boost);

        $boost->update([
            'status'      => 'creating',
            'n8n_payload' => $payload,
        ]);

        // Créer ou mettre à jour le BoostRun d'audit
        $boostRun = $this->createOrUpdateBoostRun($boost);

        if ($this->mockMode) {
            $this->simulateCreateCallback($boost, $boostRun);
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
                $boost->update(['status' => 'approved', 'n8n_payload' => null]);
                $boostRun->update(['status' => 'FAILED']);
                throw new \RuntimeException('N8N a retourné une erreur : ' . $response->status());
            }

            Log::info('N8N webhook create sent', ['boost_id' => $boost->id]);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('N8N connection error', ['boost_id' => $boost->id, 'error' => $e->getMessage()]);
            $boost->update(['status' => 'approved', 'n8n_payload' => null]);
            $boostRun->update(['status' => 'FAILED']);
            throw new \RuntimeException('Impossible de joindre N8N. Vérifiez la configuration.');
        }
    }

    /**
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
            $boost->boostRun()?->update(['status' => 'ACTIVE']);
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

            Log::info('N8N webhook activate sent', ['boost_id' => $boost->id]);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new \RuntimeException('Impossible de joindre N8N pour l\'activation.');
        }
    }

    /**
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
            $boost->boostRun()?->update(['status' => 'PAUSED']);
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
    // Payloads — conformes au PDF Architecture
    // ─────────────────────────────────────────────────────────

    /**
     * Payload complet pour la création Campaign + AdSet + Ad via N8N.
     * Conforme au PDF Architecture Technique (section 5).
     */
    private function buildCreatePayload(BoostRequest $boost): array
    {
        $page = FacebookPage::where('page_id', $boost->page_id)->first();

        if (!$page) {
            throw new \RuntimeException(
                "La page Facebook ({$boost->page_id}) associée au boost #{$boost->id} est introuvable."
            );
        }

        $target      = $boost->target;
        $durationDays = $boost->start_date->diffInDays($boost->end_date);

        // Budget en unité mineure (centimes pour EUR/USD, entier pour XOF)
        $lifetimeBudgetMinor = $this->toBudgetMinorUnit((float) $boost->budget, $boost->currency);

        // Ciblage géographique avec city key Meta si Côte d'Ivoire
        $countries  = (array) ($target['countries'] ?? []);
        $geoPayload = ['countries' => $countries];
        if (in_array('CI', $countries)) {
            $geoPayload['cities'] = [['key' => self::ABIDJAN_CITY_KEY]];
        }

        return [
            // ── Identification ──────────────────────────────
            'boost_id'     => $boost->id,
            'callback_url' => route('webhook.n8n.boost-created'),
            'mode'         => 'CREATE_PAUSED',

            // ── Compte Meta Ads ─────────────────────────────
            'ad_account_id' => $page->ad_account_id,

            // ── Post Facebook à booster ─────────────────────
            'post_id'   => $boost->post_id,
            'page_id'   => $boost->page_id,
            'page_name' => $boost->page_name,

            // ── Campaign (PDF §5.2) ─────────────────────────
            'campaign_name'          => "Boost #{$boost->id} — {$boost->page_name}",
            'objective'              => 'OUTCOME_TRAFFIC',
            'campaign_status'        => 'PAUSED',
            'special_ad_categories'  => [],   // PDF : obligatoire, même vide

            // ── Ad Set (PDF §5.3) ───────────────────────────
            'adset_name'                      => "AdSet #{$boost->id} — " . $boost->start_date->format('d/m/Y'),
            'start_time'                      => $boost->start_date->toIso8601String(),
            'end_time'                        => $boost->end_date->toIso8601String(),
            'lifetime_budget'                 => $lifetimeBudgetMinor,  // PDF : en centimes
            'currency'                        => $boost->currency,
            'billing_event'                   => 'IMPRESSIONS',
            'optimization_goal'               => 'LINK_CLICKS',
            'is_adset_budget_sharing_enabled' => false,               // PDF : ABO obligatoire
            'destination_type'                => $boost->whatsapp_url ? 'WHATSAPP' : 'WEBSITE',

            // ── Ciblage (PDF §5.3) ──────────────────────────
            'targeting' => [
                'age_min'       => (int) ($target['age_min'] ?? 18),
                'age_max'       => (int) ($target['age_max'] ?? 65),
                'genders'       => $this->mapGender($target['gender'] ?? 'all'),
                'geo_locations' => $geoPayload,
                'interests'     => $this->mapInterests($target['interests'] ?? []),
            ],

            // ── Ad creative (PDF §5.4) ──────────────────────
            // Format Meta pour booster un post existant : {page_id}_{post_id}
            'ad_name'        => "Ad — Boost Post {$boost->post_id}",
            'object_story_id'=> $boost->page_id . '_' . $boost->post_id,

            // ── WhatsApp CTA ────────────────────────────────
            'whatsapp_url' => $boost->whatsapp_url,

            // ── Durée (informatif) ──────────────────────────
            'duration_days' => (int) $durationDays,

            // ── Opérateur ───────────────────────────────────
            'operator_name'  => $boost->operator?->name ?? 'Opérateur inconnu',
            'operator_email' => $boost->operator?->email ?? '',
        ];
    }

    /**
     * Payload minimal pour activate/pause — N8N a juste besoin des IDs Meta.
     */
    private function buildActionPayload(BoostRequest $boost, string $action): array
    {
        return [
            'boost_id'         => $boost->id,
            'action'           => $action,
            'meta_campaign_id' => $boost->meta_campaign_id,
            'meta_adset_id'    => $boost->meta_adset_id,
            'meta_ad_id'       => $boost->meta_ad_id,
            'callback_url'     => route('webhook.n8n.boost-activated'),
        ];
    }

    // ─────────────────────────────────────────────────────────
    // BoostRun — audit de chaque intention de boost
    // ─────────────────────────────────────────────────────────

    private function createOrUpdateBoostRun(BoostRequest $boost): BoostRun
    {
        $postMaster = FacebookPost::where('post_id', $boost->post_id)->first();
        $durationDays = $boost->start_date->diffInDays($boost->end_date);

        $runStatus = [
            'boost_request_id' => $boost->id,
            'post_master_id'   => $postMaster?->id,
            'run_id'           => $postMaster?->last_sync_run_id,
            'requested_by'     => $boost->operator?->email ?? 'unknown',
            'status'           => 'PAUSED',
            'budget_total_cents' => $this->toBudgetMinorUnit((float) $boost->budget, $boost->currency),
            'currency'         => $boost->currency,
            'duration_days'    => (int) $durationDays,
            'targeting_json'   => $boost->target,
        ];

        // Mettre à jour le run existant ou en créer un nouveau
        $existingRun = BoostRun::where('boost_request_id', $boost->id)->latest()->first();
        if ($existingRun) {
            $existingRun->update($runStatus);
            return $existingRun;
        }

        return BoostRun::create($runStatus);
    }

    // ─────────────────────────────────────────────────────────
    // Mock — simule le callback N8N en mode développement
    // ─────────────────────────────────────────────────────────

    private function simulateCreateCallback(BoostRequest $boost, BoostRun $boostRun): void
    {
        $ts = now()->timestamp;
        $mockCampaignId = 'MOCK_CAMPAIGN_' . $boost->id . '_' . $ts;
        $mockAdsetId    = 'MOCK_ADSET_'    . $boost->id . '_' . $ts;
        $mockAdId       = 'MOCK_AD_'       . $boost->id . '_' . $ts;

        $boost->update([
            'status'           => 'paused_ready',
            'meta_campaign_id' => $mockCampaignId,
            'meta_adset_id'    => $mockAdsetId,
            'meta_ad_id'       => $mockAdId,
            'n8n_response'     => [
                'mock'        => true,
                'created_at'  => now()->toIso8601String(),
                'campaign_id' => $mockCampaignId,
                'adset_id'    => $mockAdsetId,
                'ad_id'       => $mockAdId,
                'message'     => 'Campagne créée en mode mock — prête pour activation',
            ],
        ]);

        // Créer l'AdsEntity (audit des IDs Meta)
        AdsEntity::updateOrCreate(
            ['boost_run_id' => $boostRun->id],
            [
                'campaign_id'      => $mockCampaignId,
                'adset_id'         => $mockAdsetId,
                'ad_id'            => $mockAdId,
                'campaign_status'  => 'PAUSED',
                'adset_status'     => 'PAUSED',
                'ad_status'        => 'PAUSED',
                'payload'          => [
                    'mock'         => true,
                    'created_at'   => now()->toIso8601String(),
                    'object_story_id' => $boost->page_id . '_' . $boost->post_id,
                ],
            ]
        );

        Log::info('[MOCK] N8N create simulated', [
            'boost_id'    => $boost->id,
            'campaign_id' => $mockCampaignId,
        ]);
    }

    // ─────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────

    /**
     * Convertit un budget en unité mineure selon la devise.
     * XOF : pas de décimale (1 XOF = 1 XOF)
     * EUR, USD : centimes (5.00 EUR = 500)
     */
    private function toBudgetMinorUnit(float $budget, string $currency): int
    {
        if ($currency === 'XOF') {
            return (int) round($budget);
        }
        return (int) round($budget * 100);
    }

    /**
     * Mappe le genre vers le format Meta Ads API
     * Meta : 1 = Homme, 2 = Femme, [] = Tous
     */
    private function mapGender(string $gender): array
    {
        return match ($gender) {
            'male'   => [1],
            'female' => [2],
            default  => [],
        };
    }

    /**
     * Mappe les intérêts vers le format Meta Ads API
     * (texte libre → nom d'intérêt, l'API Meta accepte les noms)
     */
    private function mapInterests(array $interests): array
    {
        return array_map(fn($interest) => ['name' => $interest], $interests);
    }
}
