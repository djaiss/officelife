<?php

declare(strict_types=1);

use App\Jobs\CheckOverdueAssetReturns;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nothing a person does makes a piece of equipment late, so it is swept daily.
Schedule::job(new CheckOverdueAssetReturns, queue: 'low')->dailyAt('07:00');
