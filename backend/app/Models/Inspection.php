<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Daily BLOWBAGETS inspection (FR-09, FR-10). Agency-scoped.
 */
class Inspection extends Model
{
    use BelongsToAgency;
    use HasFactory;

    public const STATUS_PENDING = 'Pending';
    public const STATUS_REVIEWED = 'Reviewed';

    protected $fillable = [
        'agency_id',
        'vehicle_id',
        'driver_id',
        'inspection_date',
        'review_status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'inspection_date' => 'date',
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

    public function items(): HasMany
    {
        return $this->hasMany(InspectionItem::class);
    }

    /** True when any item is flagged Has Issue. */
    public function hasIssue(): bool
    {
        return $this->items->contains(fn (InspectionItem $item) => $item->status === InspectionItem::STATUS_HAS_ISSUE);
    }

    /** Prototype RESULT pill text: "All OK" or "Has Issue". */
    public function resultLabel(): string
    {
        return $this->hasIssue() ? 'Has Issue' : 'All OK';
    }
}
