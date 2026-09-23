<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * GET /api/v1/licenses/monitoring (FR-08) — consolidated Valid / Expiring
 * Soon / Expired counts for the admin's agency, against the agency's
 * configurable license_expiry_warning_days threshold. Reports the TESDA NC II
 * (Driving) the same way (2026-09), since it is monitored identically.
 */
class LicenseMonitoringController extends Controller
{
    public function __invoke(Request $request)
    {
        $drivers = User::query()
            ->drivers()
            ->where('agency_id', $request->user()->agency_id)
            ->where('status', User::STATUS_ACTIVE)
            ->where(fn ($q) => $q
                ->whereNotNull('license_expiry_date')
                ->orWhereNotNull('nc_ii_expiry_date'))
            ->with('agency')
            ->get();

        $license = ['Valid' => 0, 'Expiring Soon' => 0, 'Expired' => 0];
        $ncIi = ['Valid' => 0, 'Expiring Soon' => 0, 'Expired' => 0];

        foreach ($drivers as $driver) {
            if ($status = $driver->licenseStatus()) {
                $license[$status]++;
            }
            if ($ncStatus = $driver->ncIiStatus()) {
                $ncIi[$ncStatus]++;
            }
        }

        return response()->json([
            'valid' => $license['Valid'],
            'expiring_soon' => $license['Expiring Soon'],
            'expired' => $license['Expired'],
            'nc_ii' => [
                'valid' => $ncIi['Valid'],
                'expiring_soon' => $ncIi['Expiring Soon'],
                'expired' => $ncIi['Expired'],
            ],
        ]);
    }
}
