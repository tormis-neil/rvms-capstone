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
    public const TYPE_BOTH = 'Mileage & Time-Based';

    public const TYPES = [
        self::TYPE_MILEAGE,
        self::TYPE_TIME,
        self::TYPE_BOTH,
    ];

    public const STATUS_UPCOMING = 'Upcoming';
    public const STATUS_DUE_SOON = 'Due Soon';
    public const STATUS_DUE = 'Due';
    public const STATUS_COMPLETED = 'Completed';

    protected $fillable = [
        'agency_id',
        'vehicle_id',
        'service_target',
        'schedule_document_path',
        'pm_type',
        'interval_km',
        'last_pm_mileage',
        'due_mileage',
        'due_date',
        'due_soon_threshold_km',
        'due_soon_threshold_days',
        'status',
        'date_serviced',
        'completion_mileage',
        'completion_repair_source',
        'completion_external_shop_name',
        'completion_receipt_path',
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

    public function usesMileage(): bool
    {
        return in_array($this->pm_type, [self::TYPE_MILEAGE, self::TYPE_BOTH], true);
    }

    public function usesTime(): bool
    {
        return in_array($this->pm_type, [self::TYPE_TIME, self::TYPE_BOTH], true);
    }

    /**
     * The status this active schedule should have, given the vehicle's current
     * mileage (mileage-based), today's date (time-based), or whichever threshold
     * is reached first (Mileage & Time-Based). Completed schedules are never
     * recalculated. Plan §6.7.
     */
    public function recalculatedStatus(?int $currentMileage = null): string
    {
        if ($this->isCompleted()) {
            return self::STATUS_COMPLETED;
        }

        $mileageStatus = self::STATUS_UPCOMING;
        if ($this->usesMileage() && $this->due_mileage !== null) {
            $due = (int) $this->due_mileage;
            $mileage = (int) ($currentMileage ?? $this->vehicle?->current_mileage ?? 0);
            $window = (int) ($this->due_soon_threshold_km ?? 0);

            if ($mileage >= $due) {
                $mileageStatus = self::STATUS_DUE;
            } elseif ($mileage >= ($due - $window)) {
                $mileageStatus = self::STATUS_DUE_SOON;
            }
        }

        $timeStatus = self::STATUS_UPCOMING;
        if ($this->usesTime() && $this->due_date !== null) {
            $today = Carbon::today();
            $dueDate = $this->due_date;
            $window = (int) ($this->due_soon_threshold_days ?? 0);

            if ($today->greaterThanOrEqualTo($dueDate)) {
                $timeStatus = self::STATUS_DUE;
            } elseif ($today->greaterThanOrEqualTo($dueDate->copy()->subDays($window))) {
                $timeStatus = self::STATUS_DUE_SOON;
            }
        }

        if ($mileageStatus === self::STATUS_DUE || $timeStatus === self::STATUS_DUE) {
            return self::STATUS_DUE;
        }

        if ($mileageStatus === self::STATUS_DUE_SOON || $timeStatus === self::STATUS_DUE_SOON) {
            return self::STATUS_DUE_SOON;
        }

        return self::STATUS_UPCOMING;
    }

    /** Badge class matching the prototype's PM status pills. */
    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_DUE => 'bg-danger',
            self::STATUS_DUE_SOON => 'bg-warning text-dark',
            self::STATUS_COMPLETED => 'bg-secondary',
            default => 'badge-upcoming', // Upcoming
        };
    }

    /** The "target" cell: displays both due mileage and due date (whichever comes first). */
    public function targetLabel(): string
    {
        $km = $this->due_mileage !== null ? number_format($this->due_mileage).' km' : null;
        $date = $this->due_date?->format('M j, Y');

        return implode(' / ', array_filter([$km, $date])) ?: '—';
    }
}
