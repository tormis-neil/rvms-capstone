<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/v1/licenses/monitoring — consolidated view of expiring and
 * expired driver licenses for the caller's agency (FR-08). The warning
 * window is the agency's configurable license_expiry_warning_days.
 */
class LicenseController extends Controller
{
    public function monitoring(Request $request): JsonResponse
    {
        $agency = $request->user()->agency;
        $warningDays = $agency->license_expiry_warning_days;

        $agencyDrivers = fn () => User::query()
            ->drivers()
            ->where('agency_id', $agency->id);

        $expired = $agencyDrivers()
            ->expired()
            ->orderBy('license_expiry_date')
            ->get();

        $expiringSoon = $agencyDrivers()
            ->expiringSoon($warningDays)
            ->orderBy('license_expiry_date')
            ->get();

        return response()->json([
            'warning_days' => $warningDays,
            'expired_count' => $expired->count(),
            'expiring_soon_count' => $expiringSoon->count(),
            'expired' => UserResource::collection($expired),
            'expiring_soon' => UserResource::collection($expiringSoon),
        ]);
    }
}
