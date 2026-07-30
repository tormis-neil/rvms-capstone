<?php

namespace App\Services;

use App\Models\DamageReport;
use App\Models\Dispatch;
use App\Models\Inspection;
use App\Models\PmSchedule;
use App\Models\RepairLog;
use App\Models\Vehicle;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * The six printable reports (FR-20), built in one place.
 *
 * The Blade page and `GET /api/v1/reports/{type}` both read from here, so a
 * printed report and the API can never describe the same records differently.
 *
 * Columns go wider than the prototype's demo table on the project lead's
 * instruction (2026-07): each report carries every field its own module screen
 * records, so a printout is a complete record rather than a summary of one.
 * Two columns are deliberately still excluded — `vehicles.status_source` and
 * `vehicles.status_changed_at` are repo-only by design decision 9 and are NOT
 * in the manuscript's data dictionary, so putting them on a printed government
 * report would surface a field the documentation does not describe.
 *
 * Reports are never paginated. A printout with a pager is not a printout, and
 * FR-20 asks for the filtered set, not a page of it.
 */
class ReportBuilder
{
    /** Slug => prototype's own card title (used verbatim on the printout). */
    public const TYPES = [
        'inspections' => 'Inspection Records',
        'damage' => 'Damage & Defects',
        'repairs-maintenance' => 'Repair & Maintenance History',
        'pm' => 'Preventive Maintenance Records',
        'dispatch' => 'Dispatch Logs',
        'vehicle-status' => 'Vehicle Status Summary',
    ];

    /** The prototype's three Date Range options, as slugs. */
    public const RANGES = [
        'all' => 'All Dates',
        'last-7-days' => 'Last 7 Days',
        'this-month' => 'This Month',
    ];

    /**
     * A current-state snapshot takes no filters at all — the prototype hides
     * the whole filter block for it and labels the card "no filters".
     */
    public const UNFILTERED_TYPES = ['vehicle-status'];

    /** PM schedules have no driver of their own; the filter would do nothing. */
    public const NO_DRIVER_FILTER_TYPES = ['pm', 'vehicle-status'];

    /**
     * Build one report.
     *
     * @param  array{range?: ?string, vehicle_id?: ?int, driver_id?: ?int}  $filters
     * @return array{title: string, columns: list<string>, rows: list<list<string>>, filter_summary: list<string>}
     */
    public function build(string $type, int $agencyId, array $filters = []): array
    {
        $spec = match ($type) {
            'inspections' => $this->inspections($agencyId, $filters),
            'damage' => $this->damage($agencyId, $filters),
            'repairs-maintenance' => $this->repairs($agencyId, $filters),
            'pm' => $this->pm($agencyId, $filters),
            'dispatch' => $this->dispatch($agencyId, $filters),
            'vehicle-status' => $this->vehicleStatus($agencyId),
        };

        return [
            'title' => self::TYPES[$type],
            'columns' => $spec['columns'],
            'rows' => $spec['rows'],
            'filter_summary' => $this->filterSummary($type, $filters),
        ];
    }

    /* ------------------------------ reports ------------------------------ */

    private function inspections(int $agencyId, array $filters): array
    {
        $rows = $this->scoped(Inspection::query(), $agencyId, $filters, 'inspection_date')
            ->with(['vehicle', 'driver', 'reviewer', 'items.checklistItem'])
            ->orderByDesc('inspection_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Inspection $i) => [
                $this->dateTime($i->created_at),
                $this->vehicleLabel($i->vehicle),
                $i->driver?->name ?? '—',
                $i->resultLabel(),
                $i->remarksSummary(),
                $i->review_status,
                $i->reviewer?->name ?? '—',
            ]);

        return [
            'columns' => ['Date & Time', 'Vehicle', 'Driver', 'Result', 'Remarks', 'Review Status', 'Reviewed By'],
            'rows' => $rows->all(),
        ];
    }

    private function damage(int $agencyId, array $filters): array
    {
        $rows = $this->scoped(DamageReport::query(), $agencyId, $filters, 'date_reported')
            ->with(['vehicle', 'driver', 'reviewer'])
            ->orderByDesc('date_reported')
            ->orderByDesc('id')
            ->get()
            ->map(fn (DamageReport $d) => [
                $this->dateTime($d->created_at),
                $this->vehicleLabel($d->vehicle),
                $d->driver?->name ?? '—',
                $d->nature_of_damage,
                $d->suspected_parts ?: '—',
                $d->photo_path ? 'Yes' : 'None',
                $d->status,
                $d->reviewer?->name ?? '—',
            ]);

        return [
            'columns' => ['Date & Time', 'Vehicle', 'Driver', 'Nature of Damage', 'Suspected Parts', 'Photo', 'Review Status', 'Reviewed By'],
            'rows' => $rows->all(),
        ];
    }

    private function repairs(int $agencyId, array $filters): array
    {
        $rows = $this->scoped(RepairLog::query(), $agencyId, $filters, 'repair_date')
            ->with(['vehicle', 'driver'])
            ->orderByDesc('repair_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (RepairLog $r) => [
                $this->date($r->repair_date),
                $this->vehicleLabel($r->vehicle),
                $r->driver?->name ?? '—',
                $r->scope_of_work,
                $r->parts_replaced ?: '—',
                $r->cost === null ? '—' : '₱'.number_format((float) $r->cost, 2),
                $r->sourceLabel(),
                $r->remarks ?: '—',
                $r->vehicle?->status ?? '—',
            ]);

        return [
            'columns' => ['Date', 'Vehicle', 'Driver', 'Scope of Work', 'Parts Replaced', 'Cost', 'Repair Source', 'Remarks', 'Vehicle Status'],
            'rows' => $rows->all(),
        ];
    }

    /**
     * Active and completed schedules in one table, exactly as the prototype
     * concatenates them — a maintenance record is the whole cycle, not two
     * separate documents.
     */
    private function pm(int $agencyId, array $filters): array
    {
        $query = PmSchedule::query()->where('agency_id', $agencyId)->with('vehicle');

        if (! empty($filters['vehicle_id'])) {
            $query->where('vehicle_id', $filters['vehicle_id']);
        }

        // A PM row's date is when it was serviced if it is done, and when it
        // falls due if it is not — so the range means "records in this period"
        // for both halves of the table.
        if ($range = $this->rangeBounds($filters['range'] ?? null)) {
            $query->where(function (Builder $q) use ($range) {
                $q->whereBetween('date_serviced', $range)
                    ->orWhereBetween('due_date', $range);
            });
        }

        $rows = $query->orderByDesc('id')->get()->map(fn (PmSchedule $p) => [
            $this->vehicleLabel($p->vehicle),
            $p->service_target,
            $p->pm_type,
            $p->interval_km ? number_format($p->interval_km).' km' : '—',
            $this->pmDueLabel($p),
            $p->status,
            $this->date($p->date_serviced),
            $p->completion_repair_source ?: '—',
            $p->completion_parts_replaced ?: '—',
            $p->completion_remarks ?: '—',
        ]);

        return [
            'columns' => ['Vehicle', 'Service Target', 'Type', 'Interval', 'Due', 'Status', 'Date Serviced', 'Repair Source', 'Parts Replaced', 'Remarks'],
            'rows' => $rows->all(),
        ];
    }

    private function dispatch(int $agencyId, array $filters): array
    {
        $rows = $this->scoped(Dispatch::query(), $agencyId, $filters, 'time_out')
            ->with(['vehicle', 'driver'])
            ->orderByDesc('time_out')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Dispatch $d) => [
                $d->missionLabel(),
                $this->vehicleLabel($d->vehicle),
                $d->driver?->name ?? '—',
                $d->location,
                $this->dateTime($d->time_out),
                $d->odometer_out === null ? '—' : number_format($d->odometer_out).' km',
                $this->dateTime($d->time_in),
                $d->odometer_in === null ? '—' : number_format($d->odometer_in).' km',
                $d->time_in === null ? 'Active' : 'Completed',
                $d->return_status ?: '—',
                $d->remarks ?: '—',
            ]);

        return [
            'columns' => ['Mission', 'Vehicle', 'Driver', 'Location', 'Time Out', 'Odometer Out', 'Time In', 'Odometer In', 'Status', 'Return Status', 'Remarks'],
            'rows' => $rows->all(),
        ];
    }

    /** A snapshot of the fleet as it stands — no date range applies. */
    private function vehicleStatus(int $agencyId): array
    {
        $rows = Vehicle::query()
            ->where('agency_id', $agencyId)
            ->with('assignedDriver')
            ->orderBy('plate_number')
            ->get()
            ->map(fn (Vehicle $v) => [
                $v->plate_number,
                $v->type,
                trim($v->make.' '.$v->model),
                $v->engine_number ?: '—',
                $v->chassis_number ?: '—',
                $v->assignedDriver?->name ?? 'Unassigned',
                $v->mileageLabel(),
                $v->status,
            ]);

        return [
            'columns' => ['Plate Number', 'Type', 'Make / Model', 'Engine No.', 'Chassis No.', 'Assigned Driver', 'Mileage', 'Status'],
            'rows' => $rows->all(),
        ];
    }

    /* ------------------------------ helpers ------------------------------ */

    /**
     * Agency scope plus the shared vehicle / driver / date-range filters.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     */
    private function scoped(Builder $query, int $agencyId, array $filters, string $dateColumn): Builder
    {
        $query->where('agency_id', $agencyId);

        if (! empty($filters['vehicle_id'])) {
            $query->where('vehicle_id', $filters['vehicle_id']);
        }

        if (! empty($filters['driver_id'])) {
            $query->where('driver_id', $filters['driver_id']);
        }

        if ($range = $this->rangeBounds($filters['range'] ?? null)) {
            $query->whereBetween($dateColumn, $range);
        }

        return $query;
    }

    /**
     * The prototype's three presets as concrete bounds, or null for All Dates.
     *
     * @return array{0: CarbonInterface, 1: CarbonInterface}|null
     */
    private function rangeBounds(?string $range): ?array
    {
        return match ($range) {
            'last-7-days' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            'this-month' => [now()->startOfMonth(), now()->endOfMonth()],
            default => null,
        };
    }

    /** The line printed under the report header, naming what was applied. */
    private function filterSummary(string $type, array $filters): array
    {
        if (in_array($type, self::UNFILTERED_TYPES, true)) {
            return ['Current snapshot of all vehicles'];
        }

        $bits = ['Date range: '.(self::RANGES[$filters['range'] ?? 'all'] ?? 'All Dates')];
        $bits[] = 'Vehicle: '.($filters['vehicle_label'] ?? 'All');

        if (! in_array($type, self::NO_DRIVER_FILTER_TYPES, true)) {
            $bits[] = 'Driver: '.($filters['driver_label'] ?? 'All');
        }

        return $bits;
    }

    /** "Fire Truck (ABC-1234)" — the prototype's vehicle cell. */
    private function vehicleLabel(?Vehicle $vehicle): string
    {
        return $vehicle === null
            ? '—'
            : $vehicle->plate_number.' ('.$vehicle->type.')';
    }

    /** Mileage-based schedules are due at a reading, time-based at a date. */
    private function pmDueLabel(PmSchedule $schedule): string
    {
        if ($schedule->pm_type === PmSchedule::TYPE_MILEAGE && $schedule->due_mileage !== null) {
            return number_format($schedule->due_mileage).' km';
        }

        return $this->date($schedule->due_date);
    }

    private function date(?CarbonInterface $value): string
    {
        return $value?->format('M j, Y') ?? '—';
    }

    private function dateTime(?CarbonInterface $value): string
    {
        return $value?->format('M j, Y g:i A') ?? '—';
    }
}
