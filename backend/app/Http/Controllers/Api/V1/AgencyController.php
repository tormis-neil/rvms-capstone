<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use Illuminate\Http\JsonResponse;

/**
 * Public agency directory for driver self-registration (FR-03).
 *
 * The mobile Sign Up screen must let a prospective driver pick their agency and
 * submit its id to POST /register. This endpoint is intentionally public (a new
 * driver has no token yet) and exposes only non-sensitive identity fields —
 * id, code, name — never the configurable thresholds or contact details on the
 * admin profile page.
 */
class AgencyController extends Controller
{
    public function index(): JsonResponse
    {
        $agencies = Agency::orderBy('id')
            ->get(['id', 'code', 'name'])
            ->map(fn (Agency $agency) => [
                'id' => $agency->id,
                'code' => $agency->code,
                'name' => $agency->name,
            ]);

        return response()->json(['data' => $agencies]);
    }
}
