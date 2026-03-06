<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Synchronisation quotidienne des stats Meta Ads (chaque nuit à 3h00)
Schedule::command('campaigns:sync-stats')->dailyAt('03:00');
