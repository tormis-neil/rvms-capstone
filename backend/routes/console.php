<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Recompute PM Due Soon/Due statuses daily (FR-14, plan §6.7).
Schedule::command('rvms:recalculate-pm')->dailyAt('01:00');
