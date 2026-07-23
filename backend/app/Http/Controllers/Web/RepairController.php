<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRepairLogRequest;
use App\Models\RepairLog;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Repair Logs dashboard page (FR-13) — the Blade twin of the /api/v1/repairs
 * endpoints. Agency-scoped; the assigned driver is derived from the vehicle.
 */
class RepairController extends Controller
{
    public function index(Request $request): View
    {
        $agencyId = $request->user()->agency_id;

        $repairs = RepairLog::query()
            ->with(['vehicle', 'driver'])
            ->when($request->filled('vehicle_id'), fn ($q) => $q->where('vehicle_id', $request->integer('vehicle_id')))
            ->when($request->filled('source'), fn ($q) => $q->where('repair_source', $request->string('source')))
            ->latest('repair_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $vehicles = Vehicle::query()
            ->with('assignedDriver')
            ->orderBy('plate_number')
            ->get(['id', 'plate_number', 'type', 'assigned_driver_id', 'status']);

        return view('repairs', compact('repairs', 'vehicles'));
    }

    public function store(StoreRepairLogRequest $request): RedirectResponse
    {
        RepairLog::create($this->payload($request));

        return redirect()->route('repairs')->with('status', 'Repair log saved.');
    }

    public function update(StoreRepairLogRequest $request, RepairLog $repair): RedirectResponse
    {
        $repair->update($this->payload($request));

        return redirect()->route('repairs')->with('status', 'Repair log updated.');
    }

    /**
     * The driver is auto-filled from the vehicle record (prototype behaviour),
     * so it is derived here rather than taken from the form.
     */
    private function payload(StoreRepairLogRequest $request): array
    {
        $data = $request->validated();
        $data['driver_id'] = Vehicle::whereKey($data['vehicle_id'])->value('assigned_driver_id');

        // Clear the shop name unless the source is an external shop.
        if ($data['repair_source'] !== RepairLog::SOURCE_EXTERNAL) {
            $data['external_shop_name'] = null;
        }

        return $data;
    }
}
