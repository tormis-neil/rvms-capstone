<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DriverController;
use App\Http\Controllers\Api\V1\InspectionChecklistController;
use App\Http\Controllers\Api\V1\InspectionController;
use App\Http\Controllers\Api\V1\LicenseMonitoringController;
use App\Http\Controllers\Api\V1\MyVehicleController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\VehicleController;
use Illuminate\Support\Facades\Route;

// Public (no token)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Authenticated (Sanctum bearer token)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::patch('/me/profile', [ProfileController::class, 'update']);

    // Admin — vehicle records (FR-05, FR-18)
    Route::middleware('role:admin')->group(function () {
        Route::get('/vehicles', [VehicleController::class, 'index']);
        Route::post('/vehicles', [VehicleController::class, 'store']);
        Route::get('/vehicles/{vehicle}', [VehicleController::class, 'show']);
        Route::put('/vehicles/{vehicle}', [VehicleController::class, 'update']);
        Route::patch('/vehicles/{vehicle}/status', [VehicleController::class, 'updateStatus']);

        // Admin — driver records (FR-03, FR-06, FR-08)
        Route::get('/drivers', [DriverController::class, 'index']);
        Route::post('/drivers', [DriverController::class, 'store']);
        Route::get('/drivers/{driver}', [DriverController::class, 'show']);
        Route::put('/drivers/{driver}', [DriverController::class, 'update']);
        Route::patch('/drivers/{driver}/approve', [DriverController::class, 'approve']);
        Route::patch('/drivers/{driver}/reject', [DriverController::class, 'reject']);
        Route::patch('/drivers/{driver}/license', [DriverController::class, 'updateLicense']);
        Route::get('/licenses/monitoring', LicenseMonitoringController::class);

        // Admin — inspection monitoring & review (FR-10).
        // {inspection} is numeric-only so text paths like /inspections/checklist
        // (a driver route below) are never captured as a wildcard id.
        Route::get('/inspections', [InspectionController::class, 'index']);
        Route::get('/inspections/frequent-issues', [InspectionController::class, 'frequentIssues']);
        Route::get('/inspections/{inspection}', [InspectionController::class, 'show'])->whereNumber('inspection');
        Route::patch('/inspections/{inspection}/review', [InspectionController::class, 'review'])->whereNumber('inspection');
    });

    // Driver — assigned vehicle(s) (FR-07), checklist + inspection submission (FR-09)
    Route::middleware('role:driver')->group(function () {
        Route::get('/my-vehicle', MyVehicleController::class);
        Route::get('/inspections/checklist', InspectionChecklistController::class);
        Route::post('/inspections', [InspectionController::class, 'store']);
    });
});
