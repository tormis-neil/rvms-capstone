<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Inspection;
use App\Models\InspectionItem;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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
        $inspections = Inspection::query()
            ->with(['vehicle', 'driver', 'items.checklistItem'])
            ->latest('inspection_date')
            ->latest('id')
            ->get();

        $pendingCount = $inspections->where('review_status', Inspection::STATUS_PENDING)->count();

        $frequentIssues = $this->frequentIssues($request->user()->agency_id);

        return view('inspections', compact('inspections', 'pendingCount', 'frequentIssues'));
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
            $inspection->vehicle()->update(['status' => $validated['vehicle_status']]);
        }

        return redirect()->route('inspections')->with('status', 'Inspection reviewed.');
    }

    /** Has-Issue counts grouped by checklist item, ranked, with last-reported date. */
    private function frequentIssues(int $agencyId)
    {
        return InspectionItem::query()
            ->join('inspections', 'inspections.id', '=', 'inspection_items.inspection_id')
            ->join('inspection_checklist_items', 'inspection_checklist_items.id', '=', 'inspection_items.checklist_item_id')
            ->where('inspections.agency_id', $agencyId)
            ->where('inspection_items.status', InspectionItem::STATUS_HAS_ISSUE)
            ->groupBy('inspection_items.checklist_item_id', 'inspection_checklist_items.name')
            ->select([
                'inspection_checklist_items.name',
                DB::raw('COUNT(*) as count'),
                DB::raw('MAX(inspections.inspection_date) as last_reported'),
            ])
            ->orderByDesc('count')
            ->orderBy('inspection_checklist_items.name')
            ->get()
            ->map(fn ($row) => [
                'issue' => $row->name,
                'count' => (int) $row->count,
                'last' => $row->last_reported ? Carbon::parse($row->last_reported)->format('M j, Y') : '—',
            ]);
    }
}
