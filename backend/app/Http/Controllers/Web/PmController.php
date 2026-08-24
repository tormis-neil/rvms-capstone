<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompletePmScheduleRequest;
use App\Http\Requests\StorePmScheduleRequest;
use App\Models\PmSchedule;
use App\Models\Vehicle;
use App\Services\MaintenanceAlerts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Preventive Maintenance dashboard page (FR-14) — the Blade twin of the
 * /api/v1/pm-schedules endpoints. Agency-scoped.
 */
class PmController extends Controller
{
    public function index(Request $request): View
    {
        // Two paginators with distinct page parameters (R10.4, NFR-01), so
        // paging the completed history does not move the active tab. Completed
        // records are the unbounded half — every cycle adds one forever.
        $active = PmSchedule::query()
            ->with('vehicle')
            ->where('status', '!=', PmSchedule::STATUS_COMPLETED)
            ->latest('id')
            ->paginate(10, ['*'], 'active_page')
            ->withQueryString();

        $completed = PmSchedule::query()
            ->with('vehicle')
            ->where('status', PmSchedule::STATUS_COMPLETED)
            ->latest('id')
            ->paginate(10, ['*'], 'completed_page')
            ->withQueryString();

        $vehicles = Vehicle::query()
            ->orderBy('plate_number')
            ->get(['id', 'plate_number', 'type', 'current_mileage']);

        return view('pm', compact('active', 'completed', 'vehicles'));
    }

    public function store(StorePmScheduleRequest $request): RedirectResponse
    {
        $schedule = PmSchedule::create($this->payload($request));

        // A schedule can be created or edited ALREADY Due Soon or Due — the
        // admin entered a past-due date or a mileage beyond the threshold.
        // Alert now rather than making them wait for tomorrow's sweep; the
        // command shares this method, so neither path double-alerts.
        app(MaintenanceAlerts::class)->raiseForSchedule($schedule);

        return redirect()->route('pm')->with('status', 'PM schedule created.');
    }

    public function update(StorePmScheduleRequest $request, PmSchedule $pmSchedule): RedirectResponse
    {
        $pmSchedule->update($this->payload($request));

        app(MaintenanceAlerts::class)->raiseForSchedule($pmSchedule->fresh());

        return redirect()->route('pm')->with('status', 'PM schedule updated.');
    }

    public function complete(CompletePmScheduleRequest $request, PmSchedule $pmSchedule): RedirectResponse
    {
        $pmSchedule->update([
            'status' => PmSchedule::STATUS_COMPLETED,
            'date_serviced' => $request->validated('date_serviced'),
            'completion_mileage' => $request->validated('completion_mileage'),
            'completion_repair_source' => $request->validated('completion_repair_source'),
            'completion_external_shop_name' => $request->validated('completion_external_shop_name'),
            // Proof of the work when it was done outside (FR-14, 2026-08).
            'completion_receipt_path' => $request->storeReceipt('completion_receipt')
                ?? $pmSchedule->completion_receipt_path,
            'completion_parts_replaced' => $request->validated('completion_parts_replaced'),
            'completion_remarks' => $request->validated('completion_remarks'),
        ]);

        return redirect()->route('pm')->with('status', 'PM marked completed.');
    }

    /** Derive due_mileage + the initial status; clear the fields the type doesn't use. */
    private function payload(StorePmScheduleRequest $request): array
    {
        $data = $request->validated();

        if ($data['pm_type'] === PmSchedule::TYPE_MILEAGE) {
            $data['due_mileage'] = (int) $data['last_pm_mileage'] + (int) $data['interval_km'];
            $data['due_date'] = null;
            $data['due_soon_threshold_days'] = null;
        } else {
            $data['interval_km'] = null;
            $data['last_pm_mileage'] = null;
            $data['due_mileage'] = null;
            $data['due_soon_threshold_km'] = null;
        }

        $data['status'] = (new PmSchedule)->forceFill($data)->recalculatedStatus(
            Vehicle::whereKey($data['vehicle_id'])->value('current_mileage')
        );

        return $data;
    }
}
