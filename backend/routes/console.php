<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Recompute PM Due Soon/Due statuses daily (FR-14, plan §6.7).
Schedule::command('rvms:recalculate-pm')->dailyAt('01:00');

// Then alert on whatever that recomputation just flipped (FR-14 → FR-21).
// Ordered deliberately: running the alerts first would announce yesterday's
// statuses. Both commands are idempotent, so a re-run does not re-alert.
Schedule::command('rvms:pm-alerts')->dailyAt('01:15');

// Driver licences approaching expiry or already expired (FR-08 → FR-21),
// against each agency's own configurable warning window.
Schedule::command('rvms:license-alerts')->dailyAt('06:00');
