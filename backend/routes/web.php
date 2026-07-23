<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DamageReportController;
use App\Http\Controllers\Web\DriverController;
use App\Http\Controllers\Web\InspectionController;
use App\Http\Controllers\Web\RepairController;
use App\Http\Controllers\Web\VehicleController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::view('/dashboard', 'dashboard')->name('dashboard');

    // Vehicles (FR-05, FR-18)
    Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles');
    Route::post('/vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
    Route::put('/vehicles/{vehicle}', [VehicleController::class, 'update'])->name('vehicles.update');
    Route::patch('/vehicles/{vehicle}/status', [VehicleController::class, 'updateStatus'])->name('vehicles.status');

    // Drivers (FR-03, FR-06, FR-08)
    Route::get('/drivers', [DriverController::class, 'index'])->name('drivers');
    Route::post('/drivers', [DriverController::class, 'store'])->name('drivers.store');
    Route::put('/drivers/{driver}', [DriverController::class, 'update'])->name('drivers.update');
    Route::patch('/drivers/{driver}/license', [DriverController::class, 'updateLicense'])->name('drivers.license');
    Route::patch('/drivers/{driver}/approve', [DriverController::class, 'approve'])->name('drivers.approve');
    Route::patch('/drivers/{driver}/reject', [DriverController::class, 'reject'])->name('drivers.reject');

    // Inspections + Damage reports share one page (FR-10, FR-12).
    Route::get('/inspections', [InspectionController::class, 'index'])->name('inspections');
    Route::patch('/inspections/{inspection}/review', [InspectionController::class, 'review'])->name('inspections.review');
    Route::patch('/damage-reports/{damageReport}/review', [DamageReportController::class, 'review'])->name('damage.review');

    // Repair logs (FR-13)
    Route::get('/repairs', [RepairController::class, 'index'])->name('repairs');
    Route::post('/repairs', [RepairController::class, 'store'])->name('repairs.store');
    Route::put('/repairs/{repair}', [RepairController::class, 'update'])->name('repairs.update');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'));
