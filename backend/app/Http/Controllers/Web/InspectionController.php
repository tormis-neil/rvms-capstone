<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DamageReport;
use App\Models\Inspection;
use App\Models\Vehicle;
use App\Services\VehicleStatusWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Inspections dashboard page (FR-10) — the Blade twin of the inspection
 * monitoring/review API. Damage reports (the second half of this prototype
 * page) arrive in R4.
 */
class InspectionController extends Controller
{
    public function index(Request $request): View
    {
        // Both tables paginated with their own page parameter (R10.4, NFR-01):
        // inspections arrive daily per driver per vehicle, the fastest-growing
        // table in the system. The pending pills count the TRUE totals with
        // their own queries, so they can never shrink to the visible page.
        $inspections = Inspection::query()
            ->with(['vehicle', 'driver', 'items.checklistItem'])
            ->latest('inspection_date')
            ->latest('id')
            ->paginate(10, ['*'], 'inspections_page')
            ->withQueryString();

        $pendingCount = Inspection::query()
            ->where('review_status', Inspection::STATUS_PENDING)
            ->count();

        // Damage reports share this page (R4) — agency-scoped, newest first.
        $damageReports = DamageReport::query()
            ->with(['vehicle', 'driver', 'reviewer'])
            ->latest('date_reported')
            ->latest('id')
            ->paginate(10, ['*'], 'damage_page')
            ->withQueryString();

        $damagePendingCount = DamageReport::query()
            ->where('status', DamageReport::STATUS_PENDING)
            ->count();

        return view('inspections', compact(
            'inspections', 'pendingCount', 'damageReports', 'damagePendingCount'
        ));
    }

    public function review(Request $request, Inspection $inspection): RedirectResponse
    {
        // Only the three manual statuses; Dispatched is written by the Dispatch
        // module alone (FR-18). No admin-remarks column (design decision 7).
        $validated = $request->validate([
            'vehicle_status' => ['nullable', Rule::in(Vehicle::MANUAL_STATUSES)],
        ]);

        $inspection->update([
            'review_status' => Inspection::STATUS_REVIEWED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        if (! empty($validated['vehicle_status'])) {
            app(VehicleStatusWriter::class)->write(
                $inspection->vehicle,
                $validated['vehicle_status'],
                VehicleStatusWriter::SOURCE_INSPECTION,
            );
        }

        return redirect()->route('inspections')->with('status', 'Inspection reviewed.');
    }
}
