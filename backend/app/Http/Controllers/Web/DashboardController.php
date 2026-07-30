<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\DashboardSummary;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Fleet Overview page (FR-19) — the Blade twin of GET /api/v1/dashboard/summary.
 *
 * Both read the same DashboardSummary service, so the page and the API can
 * never report different figures for the same agency.
 */
class DashboardController extends Controller
{
    public function index(Request $request, DashboardSummary $summary): View
    {
        $agencyId = $request->user()->agency_id;

        return view('dashboard', [
            'metrics' => $summary->counts($agencyId),
            'pendingReviews' => $summary->pendingReviews($agencyId),
            'pendingReviewCount' => $summary->pendingReviewCount($agencyId),
            'expiringLicenses' => $summary->expiringLicenses($agencyId),
            'expiringLicenseCount' => $summary->expiringLicenseAttentionCount($agencyId),
        ]);
    }
}
