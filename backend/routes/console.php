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

/*
 | The safety net under FR-21's delivery, so a handover needs no queue worker.
 |
 | Pushes are dispatched after the response (NotificationDispatcher), which
 | needs nothing running at all. This drains anything that reaches the queue
 | anyway — a job dispatched normally, a future feature that queues something,
 | or a retry — so a deployed RVMS never accumulates a silent backlog because
 | nobody was told to keep `queue:work` open.
 |
 | --stop-when-empty is what makes it safe on a schedule: the worker exits once
 | the queue is drained instead of living forever, so each tick is a short,
 | bounded run rather than a process to supervise. --max-time bounds it further
 | in case a job hangs on a slow Google call.
 */
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();
