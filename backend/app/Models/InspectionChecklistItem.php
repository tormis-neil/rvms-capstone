<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * BLOWBAGETS checklist catalog item (FR-09). Global reference table (no
 * agency scope): 12 standard items for every agency + 2 BFP-only items.
 */
class InspectionChecklistItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'is_bfp_only',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_bfp_only' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** The 12 standard items + the 2 BFP-only items, in display order. */
    public function scopeForAgencyCode($query, string $code)
    {
        return $query
            ->when($code !== 'BFP', fn ($q) => $q->where('is_bfp_only', false))
            ->orderBy('sort_order');
    }
}
