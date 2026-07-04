<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Inspection;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin dashboard Inspections page (FR-10). Session/web guard; agency
 * isolation comes from the Inspection/Vehicle global scopes.
 */
class InspectionController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'vehicle_id' => ['sometimes', 'nullable', 'integer'],
            'driver_id' => ['sometimes', 'nullable', 'integer'],
            'date' => ['sometimes', 'nullable', 'date'],
        ]);

        $inspections = Inspection::with(['vehicle', 'driver', 'reviewer', 'items.checklistItem'])
            ->when($request->filled('vehicle_id'), fn ($q) => $q->where('vehicle_id', $request->integer('vehicle_id')))
            ->when($request->filled('driver_id'), fn ($q) => $q->where('driver_id', $request->integer('driver_id')))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('inspection_date', $request->date('date')->toDateString()))
            ->orderByDesc('inspection_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $vehicles = Vehicle::orderBy('plate_number')->get();
        $drivers = User::drivers()
            ->where('agency_id', auth()->user()->agency_id)
            ->orderBy('name')
            ->get();
        $frequentIssues = Inspection::frequentIssues();

        return view('inspections', compact('inspections', 'vehicles', 'drivers', 'frequentIssues'));
    }

    public function review(Request $request, Inspection $inspection): RedirectResponse
    {
        $request->validate([
            'vehicle_status' => ['sometimes', 'nullable', Rule::in(Vehicle::STATUSES)],
        ]);

        $inspection->update([
            'review_status' => Inspection::REVIEW_REVIEWED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        if ($request->filled('vehicle_status')) {
            $inspection->vehicle->update(['status' => $request->input('vehicle_status')]);
        }

        return back()->with('status', 'Inspection marked as reviewed.');
    }
}
