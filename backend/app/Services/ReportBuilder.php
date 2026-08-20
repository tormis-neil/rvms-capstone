<?php

namespace App\Services;

use App\Models\DamageReport;
use App\Models\InspectionItem;
use App\Models\Dispatch;
use App\Models\Inspection;
use App\Models\PmSchedule;
use App\Models\RepairLog;
use App\Models\Vehicle;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

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
     * `summary` (added 2026-08, adviser consultation) carries the analysis half
     * of the report: a handful of headline figures, and optionally one ranked
     * breakdown. Every figure is derived from the SAME records that produce the
     * rows, never from a second query — so the summary can never describe a
     * different set than the table printed underneath it.
     *
     * @param  array{range?: ?string, vehicle_id?: ?int, driver_id?: ?int}  $filters
     * @return array{title: string, columns: list<string>, rows: list<list<string>>, filter_summary: list<string>, summary: array{stats: list<array{label: string, value: string}>, breakdown: array{title: string, items: list<array{label: string, count: int}>}|null}}
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
            'summary' => [
                'stats' => $spec['stats'] ?? [],
                'breakdown' => $spec['breakdown'] ?? null,
            ],
        ];
    }

    /* ------------------------------ reports ------------------------------ */

    private function inspections(int $agencyId, array $filters): array
    {
        $records = $this->scoped(Inspection::query(), $agencyId, $filters, 'inspection_date')
            ->with(['vehicle', 'driver', 'reviewer', 'items.checklistItem'])
            ->orderByDesc('inspection_date')
            ->orderByDesc('id')
            ->get();

        $rows = $records
            ->map(fn (Inspection $i) => [
                $this->dateTime($i->created_at),
                $this->vehicleLabel($i->vehicle),
                $i->driver?->name ?? '—',
                $i->resultLabel(),
                $i->remarksSummary(),
                $i->review_status,
                $i->reviewer?->name ?? '—',
            ]);

        $flagged = $records->filter(
            fn (Inspection $i) => $i->items->contains(fn ($item) => $item->status === InspectionItem::STATUS_HAS_ISSUE)
        );

        // Which checklist items are failing most often across the filtered set —
        // the fleet-wide maintenance pattern the Inspections page already ranks.
        $issueCounts = $records
            ->flatMap(fn (Inspection $i) => $i->items->filter(fn ($item) => $item->status === InspectionItem::STATUS_HAS_ISSUE))
            ->groupBy(fn ($item) => $item->checklistItem?->name ?? 'Unknown')
            ->map->count()
            ->sortDesc();

        return [
            'columns' => ['Date & Time', 'Vehicle', 'Driver', 'Result', 'Remarks', 'Review Status', 'Reviewed By'],
            'rows' => $rows->all(),
            'stats' => [
                $this->stat('Inspections Submitted', $records->count()),
                $this->stat('With Reported Issues', $flagged->count(), $records->count()),
                $this->stat('Pending Review', $records->where('review_status', Inspection::STATUS_PENDING)->count()),
            ],
            'breakdown' => $this->breakdown('Most Frequently Flagged Items', $issueCounts),
        ];
    }

    private function damage(int $agencyId, array $filters): array
    {
        $records = $this->scoped(DamageReport::query(), $agencyId, $filters, 'date_reported')
            ->with(['vehicle', 'driver', 'reviewer'])
            ->orderByDesc('date_reported')
            ->orderByDesc('id')
            ->get();

        $rows = $records
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

        // Which vehicles generate the most reports — the question a maintenance
        // officer actually asks of this report.
        $byVehicle = $records
            ->groupBy(fn (DamageReport $d) => $d->vehicle?->plate_number ?? 'Unknown')
            ->map->count()
            ->sortDesc();

        return [
            'columns' => ['Date & Time', 'Vehicle', 'Driver', 'Nature of Damage', 'Suspected Parts', 'Photo', 'Review Status', 'Reviewed By'],
            'rows' => $rows->all(),
            'stats' => [
                $this->stat('Reports Filed', $records->count()),
                $this->stat('Pending Review', $records->where('status', DamageReport::STATUS_PENDING)->count(), $records->count()),
                $this->stat('Reviewed', $records->where('status', DamageReport::STATUS_REVIEWED)->count(), $records->count()),
                $this->stat('With Photo Evidence', $records->filter(fn (DamageReport $d) => (bool) $d->photo_path)->count(), $records->count()),
            ],
            'breakdown' => $this->breakdown('Vehicles Most Reported', $byVehicle),
        ];
    }

    private function repairs(int $agencyId, array $filters): array
    {
        $records = $this->scoped(RepairLog::query(), $agencyId, $filters, 'repair_date')
            ->with(['vehicle', 'driver'])
            ->orderByDesc('repair_date')
            ->orderByDesc('id')
            ->get();

        $rows = $records
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

        // Cost is optional (FR-13), so the average is over the repairs that
        // actually carry one — dividing the total by every repair would quietly
        // understate it whenever a cost was left blank.
        $withCost = $records->filter(fn (RepairLog $r) => $r->cost !== null);
        $totalCost = (float) $withCost->sum(fn (RepairLog $r) => (float) $r->cost);

        $bySource = $records
            ->groupBy(fn (RepairLog $r) => $r->repair_source)
            ->map->count()
            ->sortDesc();

        return [
            'columns' => ['Date', 'Vehicle', 'Driver', 'Scope of Work', 'Parts Replaced', 'Cost', 'Repair Source', 'Remarks', 'Vehicle Status'],
            'rows' => $rows->all(),
            'stats' => [
                $this->stat('Repairs Logged', $records->count()),
                ['label' => 'Total Recorded Cost', 'value' => '₱'.number_format($totalCost, 2)],
                [
                    'label' => 'Average per Repair',
                    'value' => $withCost->isEmpty() ? '—' : '₱'.number_format($totalCost / $withCost->count(), 2),
                ],
                $this->stat('External Shop Repairs', $records->where('repair_source', RepairLog::SOURCE_EXTERNAL)->count(), $records->count()),
            ],
            'breakdown' => $this->breakdown('Repairs by Source', $bySource),
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

        $records = $query->orderByDesc('id')->get();

        $rows = $records->map(fn (PmSchedule $p) => [
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

        $byStatus = $records->groupBy('status')->map->count()->sortDesc();

        return [
            'columns' => ['Vehicle', 'Service Target', 'Type', 'Interval', 'Due', 'Status', 'Date Serviced', 'Repair Source', 'Parts Replaced', 'Remarks'],
            'rows' => $rows->all(),
            'stats' => [
                $this->stat('Schedules', $records->count()),
                $this->stat('Completed', $records->where('status', PmSchedule::STATUS_COMPLETED)->count(), $records->count()),
                // The two that need somebody to act — the reason this report is
                // read at all.
                $this->stat('Due', $records->where('status', PmSchedule::STATUS_DUE)->count()),
                $this->stat('Due Soon', $records->where('status', PmSchedule::STATUS_DUE_SOON)->count()),
            ],
            'breakdown' => $this->breakdown('Schedules by Status', $byStatus),
        ];
    }

    private function dispatch(int $agencyId, array $filters): array
    {
        $records = $this->scoped(Dispatch::query(), $agencyId, $filters, 'time_out')
            ->with(['vehicle', 'driver'])
            ->orderByDesc('time_out')
            ->orderByDesc('id')
            ->get();

        $rows = $records
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

        $closed = $records->filter(fn (Dispatch $d) => $d->time_in !== null);

        // Averaged over CLOSED dispatches only: an active one has no end, and
        // treating "now" as its time in would report a duration that grows
        // every time the page is refreshed.
        $averageMinutes = $closed->isEmpty()
            ? null
            : $closed->avg(fn (Dispatch $d) => $d->time_out->diffInMinutes($d->time_in));

        $byMission = $records
            ->groupBy(fn (Dispatch $d) => $d->mission_type)
            ->map->count()
            ->sortDesc();

        return [
            'columns' => ['Mission', 'Vehicle', 'Driver', 'Location', 'Time Out', 'Odometer Out', 'Time In', 'Odometer In', 'Status', 'Return Status', 'Remarks'],
            'rows' => $rows->all(),
            'stats' => [
                $this->stat('Dispatches', $records->count()),
                $this->stat('Completed', $closed->count(), $records->count()),
                $this->stat('Still Active', $records->count() - $closed->count()),
                [
                    'label' => 'Average Duration',
                    'value' => $averageMinutes === null ? '—' : $this->duration((int) round($averageMinutes)),
                ],
            ],
            'breakdown' => $this->breakdown('Dispatches by Mission Type', $byMission),
        ];
    }

    /** A snapshot of the fleet as it stands — no date range applies. */
    private function vehicleStatus(int $agencyId): array
    {
        $records = Vehicle::query()
            ->where('agency_id', $agencyId)
            ->with('assignedDriver')
            ->orderBy('plate_number')
            ->get();

        $rows = $records
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

        // Every status listed, including the ones at zero: "no vehicles are Not
        // Operational" is a finding, and a bar that silently disappears reads
        // as missing data rather than as good news.
        $byStatus = collect(Vehicle::STATUSES)
            ->mapWithKeys(fn (string $status) => [$status => $records->where('status', $status)->count()]);

        return [
            'columns' => ['Plate Number', 'Type', 'Make / Model', 'Engine No.', 'Chassis No.', 'Assigned Driver', 'Mileage', 'Status'],
            'rows' => $rows->all(),
            'stats' => [
                $this->stat('Vehicles', $records->count()),
                $this->stat('Operational', $records->where('status', Vehicle::STATUS_OPERATIONAL)->count(), $records->count()),
                $this->stat('Unavailable', $records->whereIn('status', Vehicle::OUT_OF_SERVICE_STATUSES)->count(), $records->count()),
                $this->stat('Unassigned', $records->whereNull('assigned_driver_id')->count()),
            ],
            'breakdown' => $this->breakdown('Fleet by Status', $byStatus, keepZeroes: true),
        ];
    }

    /* --------------------------- summary helpers -------------------------- */

    /**
     * One headline figure. Pass $outOf to append the share it represents.
     *
     * The percentage is what turns a count into a finding: "8 inspections
     * reported issues" is a number, "8 of 20 (40%)" is something an officer can
     * act on. Suppressed when the total is zero, because 0% of nothing is not a
     * fact about the fleet.
     *
     * @return array{label: string, value: string}
     */
    private function stat(string $label, int $count, ?int $outOf = null): array
    {
        $value = number_format($count);

        if ($outOf !== null && $outOf > 0) {
            $value .= sprintf(' of %s (%d%%)', number_format($outOf), (int) round($count / $outOf * 100));
        }

        return ['label' => $label, 'value' => $value];
    }

    /**
     * A ranked breakdown, rendered as the same server-side bars the Inspections
     * page already uses for Frequently Reported Issues — no charting library,
     * no <canvas>, and it prints, which a report has to (FR-20).
     *
     * @param  \Illuminate\Support\Collection<string, int>  $counts
     * @return array{title: string, items: list<array{label: string, count: int}>}|null
     */
    private function breakdown(string $title, Collection $counts, bool $keepZeroes = false): ?array
    {
        if (! $keepZeroes) {
            $counts = $counts->filter(fn (int $count) => $count > 0);
        }

        if ($counts->isEmpty()) {
            return null;
        }

        return [
            'title' => $title,
            // Capped: a printed report is not a place to rank forty items, and
            // the tail is never the part anybody acts on.
            'items' => $counts->take(8)
                ->map(fn (int $count, string $label) => ['label' => $label, 'count' => $count])
                ->values()
                ->all(),
        ];
    }

    /** "2h 35m" — a duration an officer reads, not a minute count. */
    private function duration(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes.'m';
        }

        return intdiv($minutes, 60).'h '.($minutes % 60).'m';
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
