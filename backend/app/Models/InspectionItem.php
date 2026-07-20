<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single checklist result within an inspection (FR-09).
 */
class InspectionItem extends Model
{
    use HasFactory;

    public const STATUS_OK = 'OK';
    public const STATUS_HAS_ISSUE = 'Has Issue';

    public $timestamps = false;

    protected $fillable = [
        'inspection_id',
        'checklist_item_id',
        'status',
        'remarks',
    ];

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    public function checklistItem(): BelongsTo
    {
        return $this->belongsTo(InspectionChecklistItem::class, 'checklist_item_id');
    }
}
