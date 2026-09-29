<?php

use App\Services\SelfieRetentionService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('attendance:purge-selfies', function (SelfieRetentionService $retention) {
    $count = $retention->purge();
    $this->info("Deleted {$count} selfie(s) taken before {$retention->cutoff()->format('d M Y')}.");
})->purpose('Delete attendance selfies once their month has ended');

Schedule::command('attendance:purge-selfies')->dailyAt('02:00');
