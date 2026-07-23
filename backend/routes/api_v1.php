<?php

use App\Http\Controllers\Api\V1\AgencyController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DamageReportController;
use App\Http\Controllers\Api\V1\DriverController;
use App\Http\Controllers\Api\V1\InspectionChecklistController;
use App\Http\Controllers\Api\V1\InspectionController;
use App\Http\Controllers\Api\V1\LicenseMonitoringController;
use App\Http\Controllers\Api\V1\MyVehicleController;
use App\Http\Controllers\Api\V1\PmScheduleController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\RepairLogController;
use App\Http\Controllers\Api\V1\VehicleController;
use Illuminate\Support\Facades\Route;

// Public (no token)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
// Agency directory for the driver self-registration dropdown (FR-03) — id/code/name only.
Route::get('/agencies', [AgencyController::class, 'index']);

// Authenticated (Sanctum bearer token)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::patch('/me/profile', [ProfileController::class, 'update']);

    // Inspection history/detail — both roles. The controller scopes a driver to
    // their OWN inspections (FR-09 history) and an admin to their agency's
    // (FR-10 monitoring). {inspection} is numeric-only so the text paths
    // /inspections/checklist and /inspections/frequent-issues below still resolve.
    Route::get('/inspections', [InspectionController::class, 'index']);
    Route::get('/inspections/{inspection}', [InspectionController::class, 'show'])->whereNumber('inspection');

    // Damage reports history/detail — both roles (driver=own FR-11, admin=agency FR-12).
    Route::get('/damage-reports', [DamageReportController::class, 'index']);
    Route::get('/damage-reports/{damageReport}', [DamageReportController::class, 'show'])->whereNumber('damageReport');

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

        // Admin — inspection monitoring & review (FR-10). index/show live in the
        // shared group above (role-branched); these two stay admin-only.
        Route::get('/inspections/frequent-issues', [InspectionController::class, 'frequentIssues']);
        Route::patch('/inspections/{inspection}/review', [InspectionController::class, 'review'])->whereNumber('inspection');

        // Admin — damage report review (FR-12). index/show are shared above.
        Route::patch('/damage-reports/{damageReport}/review', [DamageReportController::class, 'review'])->whereNumber('damageReport');

        // Admin — repair logs (FR-13)
        Route::get('/repairs', [RepairLogController::class, 'index']);
        Route::post('/repairs', [RepairLogController::class, 'store']);
        Route::get('/repairs/{repair}', [RepairLogController::class, 'show'])->whereNumber('repair');
        Route::put('/repairs/{repair}', [RepairLogController::class, 'update'])->whereNumber('repair');

        // Admin — preventive maintenance schedules (FR-14)
        Route::get('/pm-schedules', [PmScheduleController::class, 'index']);
        Route::post('/pm-schedules', [PmScheduleController::class, 'store']);
        Route::get('/pm-schedules/{pmSchedule}', [PmScheduleController::class, 'show'])->whereNumber('pmSchedule');
        Route::put('/pm-schedules/{pmSchedule}', [PmScheduleController::class, 'update'])->whereNumber('pmSchedule');
        Route::patch('/pm-schedules/{pmSchedule}/complete', [PmScheduleController::class, 'complete'])->whereNumber('pmSchedule');
    });

    // Driver — assigned vehicle(s) (FR-07), checklist + inspection submission (FR-09),
    // damage report submission (FR-11)
    Route::middleware('role:driver')->group(function () {
        Route::get('/my-vehicle', MyVehicleController::class);
        Route::get('/inspections/checklist', InspectionChecklistController::class);
        Route::post('/inspections', [InspectionController::class, 'store']);
        Route::post('/damage-reports', [DamageReportController::class, 'store']);
    });
});
