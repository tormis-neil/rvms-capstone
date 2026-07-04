<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DriverController;
use App\Http\Controllers\Api\V1\LicenseController;
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

    // Driver-only endpoints
    Route::middleware('role:driver')->group(function () {
        Route::get('/my-vehicle', [MyVehicleController::class, 'show']);
    });

    // Admin-only endpoints
    Route::middleware('role:admin')->group(function () {
        Route::get('/vehicles', [VehicleController::class, 'index']);
        Route::post('/vehicles', [VehicleController::class, 'store']);
        Route::get('/vehicles/{vehicle}', [VehicleController::class, 'show']);
        Route::put('/vehicles/{vehicle}', [VehicleController::class, 'update']);
        Route::patch('/vehicles/{vehicle}/status', [VehicleController::class, 'updateStatus']);

        Route::get('/drivers', [DriverController::class, 'index']);
        Route::post('/drivers', [DriverController::class, 'store']);
        Route::get('/drivers/{driver}', [DriverController::class, 'show']);
        Route::put('/drivers/{driver}', [DriverController::class, 'update']);
        Route::patch('/drivers/{driver}/approve', [DriverController::class, 'approve']);
        Route::patch('/drivers/{driver}/reject', [DriverController::class, 'reject']);

        Route::get('/licenses/monitoring', [LicenseController::class, 'monitoring']);
    });
});
