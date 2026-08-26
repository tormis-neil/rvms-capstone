<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\DamageReportController;
use App\Http\Controllers\Web\DispatchController;
use App\Http\Controllers\Web\DriverController;
use App\Http\Controllers\Web\InspectionController;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\PmController;
use App\Http\Controllers\Web\RepairController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\ReportController;
use App\Http\Controllers\Web\VehicleController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

// no-store keeps signed-in pages out of the browser's back/forward cache, so the
// Back button re-fetches instead of replaying a snapshot with a stale vehicle
// status — and so the pages are not recoverable after sign-out (NFR-02).
Route::middleware(['auth', 'role:admin', \App\Http\Middleware\NoStoreDashboard::class])->group(function () {
    // Fleet Overview (FR-19) — live counts, no longer a static view.
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Vehicles (FR-05, FR-18)
    Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles');
    Route::post('/vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
    Route::put('/vehicles/{vehicle}', [VehicleController::class, 'update'])->name('vehicles.update');
    Route::patch('/vehicles/{vehicle}/status', [VehicleController::class, 'updateStatus'])->name('vehicles.status');

    // Drivers (FR-03, FR-06, FR-08)
    Route::get('/drivers', [DriverController::class, 'index'])->name('drivers');
    Route::post('/drivers', [DriverController::class, 'store'])->name('drivers.store');
    Route::put('/drivers/{driver}', [DriverController::class, 'update'])->name('drivers.update');
    // No separate licence route: a renewed expiry date is recorded through the
    // Edit Driver form above, beside the licence number it belongs with
    // (2026-08 — see the note in drivers.blade.php).
    Route::patch('/drivers/{driver}/approve', [DriverController::class, 'approve'])->name('drivers.approve');
    Route::patch('/drivers/{driver}/reject', [DriverController::class, 'reject'])->name('drivers.reject');
    // A driver's password, when they can no longer sign in (FR-22).
    Route::patch('/drivers/{driver}/password', [DriverController::class, 'resetPassword'])->name('drivers.password');
    // The agency's licence warning window (FR-08). It sits on the Drivers page,
    // beside the licence summary cards it governs — one value shared by every
    // driver of the agency, not a property of any one of them.
    Route::patch('/agency/license-window', [DriverController::class, 'updateLicenseWindow'])->name('agency.license-window');

    // Inspections + Damage reports share one page (FR-10, FR-12).
    Route::get('/inspections', [InspectionController::class, 'index'])->name('inspections');
    Route::patch('/inspections/{inspection}/review', [InspectionController::class, 'review'])->name('inspections.review');
    Route::patch('/damage-reports/{damageReport}/review', [DamageReportController::class, 'review'])->name('damage.review');

    // Repair logs (FR-13)
    Route::get('/repairs', [RepairController::class, 'index'])->name('repairs');
    Route::post('/repairs', [RepairController::class, 'store'])->name('repairs.store');
    Route::put('/repairs/{repair}', [RepairController::class, 'update'])->name('repairs.update');

    // Preventive maintenance (FR-14)
    Route::get('/pm', [PmController::class, 'index'])->name('pm');
    Route::post('/pm', [PmController::class, 'store'])->name('pm.store');
    Route::put('/pm/{pmSchedule}', [PmController::class, 'update'])->name('pm.update');
    Route::patch('/pm/{pmSchedule}/complete', [PmController::class, 'complete'])->name('pm.complete');

    // Dispatch + availability (FR-15, FR-16, FR-17)
    Route::get('/dispatch', [DispatchController::class, 'index'])->name('dispatch');
    Route::post('/dispatch', [DispatchController::class, 'store'])->name('dispatch.store');
    Route::put('/dispatch/{dispatch}', [DispatchController::class, 'update'])->name('dispatch.update');
    Route::patch('/dispatch/{dispatch}/close', [DispatchController::class, 'close'])->name('dispatch.close');

    // Profile (FR-04) — the admin's own account. Agency details are read-only
    // (design decision 7: no FR backs editing agency information).
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Reports (FR-20). One route: the page, plus the generated report when a
    // ?type= is present — the prototype renders its report into the same page.
    Route::get('/reports', [ReportController::class, 'index'])
        ->middleware('throttle:20,1')
        ->name('reports');

    // Notifications (FR-21). Reached from the bell's "View All Notifications"
    // link — the prototype has no sidebar item for it either.
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::patch('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    // Clearing removes only rows the admin has already read (FR-21 addition,
    // project-lead approved 2026-07) — declared before the {notification}
    // route so "clear-read" is never taken for a record id.
    Route::delete('/notifications/clear-read', [NotificationController::class, 'clearRead'])->name('notifications.clear-read');
    // Opening one marks it read and forwards to the module it concerns.
    Route::post('/notifications/{notification}', [NotificationController::class, 'open'])
        ->whereNumber('notification')->name('notifications.open');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'));
