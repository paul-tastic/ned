<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Prune old data daily (1 year retention)
Schedule::command('ned:prune-banned-ips')->daily();
Schedule::command('ned:prune-metrics')->daily();
