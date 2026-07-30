<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DashboardSummary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/v1/dashboard/summary (FR-19).
 *
 * The eight real-time counts for the caller's own agency. Admin-only: the
 * figures are fleet-wide and a driver has no business reading their agency's
 * totals. Shares DashboardSummary with the Blade page so the two can never
 * disagree.
 */
class DashboardController extends Controller
{
    public function summary(Request $request, DashboardSummary $summary): JsonResponse
    {
        return response()->json([
            'data' => $summary->counts($request->user()->agency_id),
        ]);
    }
}
