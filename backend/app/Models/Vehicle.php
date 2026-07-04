<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vehicle extends Model
{
    /** @use HasFactory<\Database\Factories\VehicleFactory> */
    use BelongsToAgency, HasFactory;

    /** The four operational statuses — the single shared status field (FR-18). */
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
    ];

    protected function casts(): array
    {
        return [
            'current_mileage' => 'integer',
        ];
    }

    public function assignedDriver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_driver_id');
    }
}
