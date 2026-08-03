<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 | R10 sub-task 7 — every scheduled command carries the same two guards.
 |
 | withoutOverlapping: a slow run must never be joined by the next tick. All
 | three walk every schedule or driver in the agency, and two concurrent
 | sweeps would double-write statuses and race the alert idempotency.
 |
 | onOneServer: harmless today (one machine) and correct the moment the
 | agencies ever run a second — without it, every server would sweep.
 |
 | The commands are individually idempotent (MaintenanceAlerts owns the
 | per-record rules), so these guards prevent wasted work and racing, not
 | duplicate alerts. Those are impossible by construction.
 */

// Recompute PM Due Soon/Due statuses daily (FR-14, plan §6.7).
Schedule::command('rvms:recalculate-pm')
    ->dailyAt('01:00')
    ->withoutOverlapping()
    ->onOneServer();

// Then alert on whatever that recomputation just flipped (FR-14 → FR-21).
// Ordered deliberately: running the alerts first would announce yesterday's
// statuses. Both commands are idempotent, so a re-run does not re-alert.
Schedule::command('rvms:pm-alerts')
    ->dailyAt('01:15')
    ->withoutOverlapping()
    ->onOneServer();

// Driver licences approaching expiry or already expired (FR-08 → FR-21),
// against each agency's own configurable warning window.
Schedule::command('rvms:license-alerts')
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->onOneServer();
