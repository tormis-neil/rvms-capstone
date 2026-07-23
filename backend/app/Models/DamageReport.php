<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Driver-submitted damage report (FR-11, FR-12). Agency-scoped.
 */
class DamageReport extends Model
{
    use BelongsToAgency;
    use HasFactory;

    public const STATUS_PENDING = 'Pending';
    public const STATUS_REVIEWED = 'Reviewed';

    protected $fillable = [
        'agency_id',
        'vehicle_id',
        'driver_id',
        'nature_of_damage',
        'suspected_parts',
        'photo_path',
        'date_reported',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'date_reported' => 'date',
            'reviewed_at' => 'datetime',
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

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** DATE REPORTED label: Today / Yesterday / "Mon j, Y" from the submission time. */
    public function dateLabel(): string
    {
        $when = $this->created_at ?? $this->date_reported;

        if ($when->isToday()) {
            return 'Today';
        }
        if ($when->isYesterday()) {
            return 'Yesterday';
        }

        return $when->format('M j, Y');
    }

    public function timeLabel(): string
    {
        return ($this->created_at ?? $this->date_reported)->format('h:i A');
    }

    public function statusBadgeClass(): string
    {
        return $this->status === self::STATUS_REVIEWED ? 'badge-reviewed' : 'badge-pending';
    }
}
