<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Reference catalog for the BLOWBAGETS daily checklist (FR-09):
 * 12 standard items + 2 BFP-only items (Hydraulic System, Fire Pump).
 * Seeded once; not agency-scoped (shared catalog).
 */
class InspectionChecklistItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'is_bfp_only', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_bfp_only' => 'boolean',
        ];
    }

    /**
     * The checklist for one agency: everyone gets the 12 standard items;
     * BFP also gets the two BFP-only items (FR-09).
     */
    public function scopeForAgency(Builder $query, Agency $agency): Builder
    {
        return $query
            ->when($agency->code !== 'BFP', fn ($q) => $q->where('is_bfp_only', false))
            ->orderBy('sort_order');
    }
}
