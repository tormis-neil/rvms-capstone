<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dispatch log (FR-15, FR-16, FR-17). Agency-scoped. Active while time_in is
 * null; completed once closed.
 */
class Dispatch extends Model
{
    use BelongsToAgency;
    use HasFactory;

    public const MISSION_OTHERS = 'Others';

    public const MISSION_TYPES = [
        'Fire Response',
        'Medical Response',
        'Rescue Operation',
        'Patrol',
        'Administrative Travel',
        self::MISSION_OTHERS,
    ];

    /** Return statuses on close — the three manual statuses (no Dispatched, FR-16). */
    public const RETURN_STATUSES = [
        Vehicle::STATUS_OPERATIONAL,
        Vehicle::STATUS_NOT_OPERATIONAL,
        Vehicle::STATUS_UNDER_PM,
    ];

    protected $fillable = [
        'agency_id',
        'vehicle_id',
        'driver_id',
        'mission_type',
        'mission_other',
        'location',
        'time_out',
        'odometer_out',
        'time_in',
        'odometer_in',
        'return_status',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'time_out' => 'datetime',
            'time_in' => 'datetime',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function isActive(): bool
    {
        return $this->time_in === null;
    }

    /** The displayed mission, resolving "Others" to its free-text detail. */
    public function missionLabel(): string
    {
        if ($this->mission_type === self::MISSION_OTHERS && $this->mission_other) {
            return $this->mission_other;
        }

        return $this->mission_type;
    }

    public function scopeActive($query)
    {
        return $query->whereNull('time_in');
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('time_in');
    }
}
