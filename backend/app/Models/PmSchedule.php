<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Preventive maintenance schedule (FR-14). Agency-scoped. Status is derived by
 * the scheduled rvms:recalculate-pm job from current mileage or today's date
 * against the configurable Due-Soon thresholds.
 */
class PmSchedule extends Model
{
    use BelongsToAgency;
    use HasFactory;

    public const TYPE_MILEAGE = 'Mileage-Based';
    public const TYPE_TIME = 'Time-Based';

    public const STATUS_UPCOMING = 'Upcoming';
    public const STATUS_DUE_SOON = 'Due Soon';
    public const STATUS_DUE = 'Due';
    public const STATUS_COMPLETED = 'Completed';

    protected $fillable = [
        'agency_id',
        'vehicle_id',
        'service_target',
        'pm_type',
        'interval_km',
        'last_pm_mileage',
        'due_mileage',
        'due_date',
        'due_soon_threshold_km',
        'due_soon_threshold_days',
        'status',
        'date_serviced',
        'completion_repair_source',
        'completion_parts_replaced',
        'completion_remarks',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'date_serviced' => 'date',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * The status this active schedule should have, given the vehicle's current
     * mileage (mileage-based) or today's date (time-based). Completed schedules
     * are never recalculated. Plan §6.7.
     */
    public function recalculatedStatus(?int $currentMileage = null): string
    {
        if ($this->isCompleted()) {
            return self::STATUS_COMPLETED;
        }

        if ($this->pm_type === self::TYPE_MILEAGE) {
            $due = (int) $this->due_mileage;
            $mileage = (int) ($currentMileage ?? $this->vehicle?->current_mileage ?? 0);
            $window = (int) ($this->due_soon_threshold_km ?? 0);

            if ($mileage >= $due) {
                return self::STATUS_DUE;
            }

            return $mileage >= ($due - $window) ? self::STATUS_DUE_SOON : self::STATUS_UPCOMING;
        }

        // Time-based
        $today = Carbon::today();
        $dueDate = $this->due_date;

        if ($dueDate === null) {
            return self::STATUS_UPCOMING;
        }

        if ($today->greaterThanOrEqualTo($dueDate)) {
            return self::STATUS_DUE;
        }

        $window = (int) ($this->due_soon_threshold_days ?? 0);

        return $today->greaterThanOrEqualTo($dueDate->copy()->subDays($window))
            ? self::STATUS_DUE_SOON
            : self::STATUS_UPCOMING;
    }

    /** Badge class matching the prototype's status pills. */
    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_DUE => 'badge-not-operational',
            self::STATUS_DUE_SOON => 'badge-under-pm',
            self::STATUS_COMPLETED => 'badge-operational',
            default => 'bg-light text-dark border', // Upcoming
        };
    }

    /** The "target" cell: due mileage or due date depending on type. */
    public function targetLabel(): string
    {
        if ($this->pm_type === self::TYPE_MILEAGE) {
            return $this->due_mileage !== null ? number_format($this->due_mileage).' km' : '—';
        }

        return $this->due_date?->format('M j, Y') ?? '—';
    }
}
