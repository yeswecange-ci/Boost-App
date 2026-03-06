<?php

namespace App\Console\Commands;

use App\Models\BoostCampaign;
use App\Services\MetaCampaignInsightsService;
use Illuminate\Console\Command;

class SyncCampaignStats extends Command
{
    protected $signature = 'campaigns:sync-stats
                            {--id=    : Synchroniser une campagne spécifique (ID BDD)}
                            {--days=  : Nombre de jours à récupérer (défaut : depuis launched_at ou 30 jours)}';

    protected $description = 'Synchronise les statistiques Meta Ads de toutes les campagnes actives';

    public function handle(MetaCampaignInsightsService $service): int
    {
        $query = BoostCampaign::whereNotNull('meta_campaign_id')
            ->whereIn('execution_status', ['active', 'paused_ready', 'done']);

        if ($id = $this->option('id')) {
            $query->where('id', $id);
        }

        $campaigns = $query->get();

        if ($campaigns->isEmpty()) {
            $this->info('Aucune campagne à synchroniser.');
            return self::SUCCESS;
        }

        $this->info("Synchronisation des stats pour {$campaigns->count()} campagne(s)…");
        $bar = $this->output->createProgressBar($campaigns->count());
        $bar->start();

        $total   = 0;
        $errors  = 0;
        $days    = (int) ($this->option('days') ?: 0);

        foreach ($campaigns as $campaign) {
            try {
                $since = $this->resolveSince($campaign, $days);
                $until = now()->format('Y-m-d');

                $count  = $service->upsertForCampaign($campaign, $since, $until);
                $total += $count;
            } catch (\Throwable $e) {
                $errors++;
                $this->newLine();
                $this->error("  Campagne #{$campaign->id} ({$campaign->campaign_name}) : {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Terminé — {$total} ligne(s) synchronisée(s)" . ($errors ? ", {$errors} erreur(s)." : '.'));

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function resolveSince(BoostCampaign $campaign, int $optionDays): string
    {
        if ($optionDays > 0) {
            return now()->subDays($optionDays)->format('Y-m-d');
        }

        // Depuis le lancement, max 90 jours
        if ($campaign->launched_at) {
            $daysAgo = (int) now()->diffInDays($campaign->launched_at);
            $daysAgo = min($daysAgo + 1, 90);
            return now()->subDays($daysAgo)->format('Y-m-d');
        }

        return now()->subDays(30)->format('Y-m-d');
    }
}
