<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompletePmScheduleRequest;
use App\Http\Requests\StorePmScheduleRequest;
use App\Http\Resources\PmScheduleResource;
use App\Models\PmSchedule;
use Illuminate\Http\Request;

/**
 * Preventive maintenance schedules API (FR-14) — admin only. Agency scoping is
 * automatic (BelongsToAgency), so a cross-agency {pmSchedule} binding 404s.
 */
class PmScheduleController extends Controller
{
    public function index(Request $request)
    {
        $schedules = PmSchedule::query()
            ->with('vehicle')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('vehicle_id'), fn ($q) => $q->where('vehicle_id', $request->integer('vehicle_id')))
            ->latest('id')
            ->paginate(10);

        return PmScheduleResource::collection($schedules);
    }

    public function store(StorePmScheduleRequest $request)
    {
        $schedule = PmSchedule::create($this->payload($request));

        return PmScheduleResource::make($schedule->load('vehicle'))
            ->response()
            ->setStatusCode(201);
    }

    public function show(PmSchedule $pmSchedule)
    {
        return PmScheduleResource::make($pmSchedule->load('vehicle'));
    }

    public function update(StorePmScheduleRequest $request, PmSchedule $pmSchedule)
    {
        $pmSchedule->update($this->payload($request));

        return PmScheduleResource::make($pmSchedule->fresh()->load('vehicle'));
    }

    /**
     * PATCH /pm-schedules/{id}/complete — record the completion (FR-14). The
     * status becomes Completed; the next cycle is NEVER auto-created.
     */
    public function complete(CompletePmScheduleRequest $request, PmSchedule $pmSchedule)
    {
        $pmSchedule->update([
            'status' => PmSchedule::STATUS_COMPLETED,
            'date_serviced' => $request->validated('date_serviced'),
            'completion_repair_source' => $request->validated('completion_repair_source'),
            'completion_parts_replaced' => $request->validated('completion_parts_replaced'),
            'completion_remarks' => $request->validated('completion_remarks'),
        ]);

        return PmScheduleResource::make($pmSchedule->fresh()->load('vehicle'));
    }

    /**
     * Build the persisted attributes, deriving due_mileage for mileage-based
     * schedules and clearing the fields that don't apply to the chosen type.
     */
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

        // Newly created/edited schedules start from a freshly computed status.
        $schedule = (new PmSchedule)->forceFill($data);
        $data['status'] = $schedule->recalculatedStatus(
            \App\Models\Vehicle::whereKey($data['vehicle_id'])->value('current_mileage')
        );

        return $data;
    }
}
