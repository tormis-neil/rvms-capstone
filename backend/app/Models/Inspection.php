<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inspection extends Model
{
    /** @use HasFactory<\Database\Factories\InspectionFactory> */
    use BelongsToAgency, HasFactory;

    public const REVIEW_PENDING = 'Pending';
    public const REVIEW_REVIEWED = 'Reviewed';

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

    /** Number of items flagged "Has Issue" (FR-10). */
    public function issueCount(): int
    {
        return $this->items->where('status', InspectionItem::STATUS_HAS_ISSUE)->count();
    }

    /** Human-readable result summary for lists (FR-10). */
    public function resultLabel(): string
    {
        $issues = $this->issueCount();

        return $issues === 0
            ? 'All OK'
            : $issues.' '.($issues === 1 ? 'issue' : 'issues');
    }

    /**
     * Frequently reported issues (FR-10): "Has Issue" counts grouped by
     * checklist item, most frequent first. Agency-scoped through the
     * Inspection global scope on the subquery.
     *
     * @return \Illuminate\Support\Collection<int, object{checklist_item_id: int, name: string, count: int}>
     */
    public static function frequentIssues(): \Illuminate\Support\Collection
    {
        return InspectionItem::query()
            ->where('inspection_items.status', InspectionItem::STATUS_HAS_ISSUE)
            ->whereIn('inspection_items.inspection_id', self::query()->select('id'))
            ->join('inspection_checklist_items', 'inspection_checklist_items.id', '=', 'inspection_items.checklist_item_id')
            ->groupBy('inspection_items.checklist_item_id', 'inspection_checklist_items.name')
            ->orderByDesc('count')
            ->orderBy('inspection_checklist_items.name')
            ->selectRaw('inspection_items.checklist_item_id, inspection_checklist_items.name, COUNT(*) as count')
            ->get();
    }
}
