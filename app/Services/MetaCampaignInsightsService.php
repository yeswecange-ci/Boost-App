<?php

namespace App\Services;

use App\Models\BoostCampaign;
use App\Models\CampaignAnalytics;
use App\Models\FacebookPage;
use App\Models\FacebookPost;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaCampaignInsightsService
{
    private const API_VERSION = 'v23.0';
    private const FIELDS      = 'impressions,reach,clicks,spend,cpm,cpc,ctr';

    /**
     * Appelle Meta Graph API et retourne les insights journaliers bruts.
     *
     * @throws \RuntimeException si l'API retourne une erreur
     */
    public function fetchInsights(BoostCampaign $campaign, string $since, string $until): array
    {
        $token = $this->resolveToken($campaign);

        if (!$token) {
            throw new \RuntimeException('Token Meta introuvable pour la page de cette campagne.');
        }
        if (!$campaign->meta_campaign_id) {
            throw new \RuntimeException('La campagne n\'a pas encore d\'ID Meta (pas encore boostée).');
        }

        $response = Http::timeout(30)->get(
            "https://graph.facebook.com/" . self::API_VERSION . "/{$campaign->meta_campaign_id}/insights",
            [
                'fields'         => self::FIELDS,
                'time_range'     => json_encode(['since' => $since, 'until' => $until]),
                'time_increment' => 1,   // 1 ligne par jour
                'access_token'   => $token,
            ]
        );

        $json = $response->json();

        if ($response->failed() || isset($json['error'])) {
            $msg = $json['error']['message'] ?? "HTTP {$response->status()}";
            throw new \RuntimeException("Meta Insights API : {$msg}");
        }

        return $json['data'] ?? [];
    }

    /**
     * Fetche les insights et les upserte dans campaign_analytics.
     * Retourne le nombre de lignes insérées/mises à jour.
     */
    public function upsertForCampaign(BoostCampaign $campaign, string $since, string $until): int
    {
        $rows  = $this->fetchInsights($campaign, $since, $until);
        $count = 0;

        foreach ($rows as $row) {
            // Meta retourne 'date_start' pour le jour
            $date = $row['date_start'] ?? null;
            if (!$date) continue;

            CampaignAnalytics::updateOrCreate(
                [
                    'boost_campaign_id' => $campaign->id,
                    'date_snapshot'     => $date,
                ],
                [
                    'impressions' => (int)   ($row['impressions'] ?? 0),
                    'reach'       => (int)   ($row['reach']       ?? 0),
                    'clicks'      => (int)   ($row['clicks']      ?? 0),
                    'spend'       => (float) ($row['spend']       ?? 0),
                    'cpm'         => (float) ($row['cpm']         ?? 0),
                    'cpc'         => (float) ($row['cpc']         ?? 0),
                    'ctr'         => (float) ($row['ctr']         ?? 0),
                ]
            );
            $count++;
        }

        Log::info("MetaCampaignInsights: campaign #{$campaign->id} → {$count} rows upserted ({$since} → {$until})");

        return $count;
    }

    // ── Helpers privés ─────────────────────────────────────────────

    private function resolveToken(BoostCampaign $campaign): ?string
    {
        $post = FacebookPost::where('post_id', $campaign->post_id)->first();
        $page = $post ? FacebookPage::find($post->facebook_page_id) : null;

        return $page?->access_token;
    }
}
