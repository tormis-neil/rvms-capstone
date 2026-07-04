<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One checklist line of a submitted inspection (FR-09).
 * Remarks are required when status = Has Issue (enforced at validation).
 */
class InspectionItem extends Model
{
    public $timestamps = false;

    public const STATUS_OK = 'OK';
    public const STATUS_HAS_ISSUE = 'Has Issue';

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
