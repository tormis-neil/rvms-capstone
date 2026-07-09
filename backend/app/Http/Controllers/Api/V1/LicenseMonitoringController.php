<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * GET /api/v1/licenses/monitoring (FR-08) — consolidated Valid / Expiring
 * Soon / Expired counts for the admin's agency, against the agency's
 * configurable license_expiry_warning_days threshold.
 */
class LicenseMonitoringController extends Controller
{
    public function __invoke(Request $request)
    {
        $drivers = User::query()
            ->drivers()
            ->where('agency_id', $request->user()->agency_id)
            ->where('status', User::STATUS_ACTIVE)
            ->whereNotNull('license_expiry_date')
            ->with('agency')
            ->get();

        $counts = ['Valid' => 0, 'Expiring Soon' => 0, 'Expired' => 0];

        foreach ($drivers as $driver) {
            $counts[$driver->licenseStatus()]++;
        }

        return response()->json([
            'valid' => $counts['Valid'],
            'expiring_soon' => $counts['Expiring Soon'],
            'expired' => $counts['Expired'],
        ]);
    }
}
