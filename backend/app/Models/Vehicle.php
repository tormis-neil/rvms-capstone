<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Agency rescue vehicle (FR-05). Carries the single shared operational
 * status (FR-18) — exactly four values, written from every module.
 */
class Vehicle extends Model
{
    use BelongsToAgency;
    use HasFactory;
    use SoftDeletes;

    public const STATUS_OPERATIONAL = 'Operational';

    public const STATUS_DISPATCHED = 'Dispatched';

    public const STATUS_NOT_OPERATIONAL = 'Not Operational';

    public const STATUS_UNDER_PM = 'Under Preventive Maintenance';

    public const STATUSES = [
        self::STATUS_OPERATIONAL,
        self::STATUS_DISPATCHED,
        self::STATUS_NOT_OPERATIONAL,
        self::STATUS_UNDER_PM,
    ];

    /**
     * Statuses an admin may set by hand; Dispatched is written only by the
     * Dispatch module (prototype status modal note, FR-15/FR-18).
     */
    public const MANUAL_STATUSES = [
        self::STATUS_OPERATIONAL,
        self::STATUS_NOT_OPERATIONAL,
        self::STATUS_UNDER_PM,
    ];

    /** Badge class per status — mirrors the prototype's STATUS_BADGE map. */
    public const STATUS_BADGES = [
        self::STATUS_OPERATIONAL => 'badge-operational',
        self::STATUS_DISPATCHED => 'badge-dispatched',
        self::STATUS_NOT_OPERATIONAL => 'badge-not-operational',
        self::STATUS_UNDER_PM => 'badge-pm',
    ];

    protected $fillable = [
        'agency_id',
        'assigned_driver_id',
        'type',
        'plate_number',
        'make',
        'model',
        'engine_number',
        'chassis_number',
        'current_mileage',
        'status',
        'status_source',
        'status_changed_at',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'current_mileage' => 'integer',
            'status_changed_at' => 'datetime',
        ];
    }

    /**
     * Provenance line for the status-change confirmation, e.g.
     * "set Jul 24, 2026 · PM Schedules". Empty when the status predates
     * tracking (seeded or migrated rows).
     */
    public function statusOriginLabel(): string
    {
        if (! $this->status_source) {
            return '';
        }

        return $this->status_changed_at
            ? 'set '.$this->status_changed_at->format('M j, Y g:i A').' · '.$this->status_source
            : 'set from '.$this->status_source;
    }

    public function assignedDriver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_driver_id')->withTrashed();
    }

    public function badgeClass(): string
    {
        return self::STATUS_BADGES[$this->status] ?? 'badge-operational';
    }

    /** "45,230 km" — the prototype's mileage display format. */
    public function mileageLabel(): string
    {
        return number_format($this->current_mileage).' km';
    }
}
