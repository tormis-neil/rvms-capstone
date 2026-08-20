<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Agency rescue vehicle (FR-05). Carries the single shared operational
 * status (FR-18) — exactly four values, written from every module.
 */
class Vehicle extends Model
{
    use BelongsToAgency;
    use HasFactory;

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

    /**
     * Statuses meaning the vehicle is off the road (2026-08, adviser-reported).
     *
     * A BLOWBAGETS inspection is a PRE-TRIP safety check, so submitting one for
     * a vehicle in these states records a trip that cannot happen — and an
     * all-OK checklist on a vehicle the system calls broken is a contradiction
     * stored in the database. Dispatched is deliberately absent: a vehicle out
     * on a mission was inspected before it left and can be inspected again when
     * it returns.
     *
     * Damage reports are deliberately NOT restricted by this list. A vehicle is
     * usually Not Operational BECAUSE it is damaged, so blocking reports would
     * make a second, unrelated fault unreportable — the same reasoning that
     * keeps multiple damage reports per vehicle allowed (design decision 10),
     * and the exact failure the GSO Motorpool described when they reported
     * finding further defects during pre-inspection.
     */
    public const OUT_OF_SERVICE_STATUSES = [
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
        return $this->belongsTo(User::class, 'assigned_driver_id');
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

    /** False when the vehicle is off the road — see OUT_OF_SERVICE_STATUSES. */
    public function isInService(): bool
    {
        return ! in_array($this->status, self::OUT_OF_SERVICE_STATUSES, true);
    }
}
