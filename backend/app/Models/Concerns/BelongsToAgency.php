<?php

namespace App\Models\Concerns;

use App\Models\Agency;
use App\Models\Scopes\AgencyScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Attach to every agency-scoped model (FR-02): adds the global
 * AgencyScope filter, auto-stamps agency_id from the authenticated
 * user on create, and provides the agency() relation.
 */
trait BelongsToAgency
{
    protected static function bootBelongsToAgency(): void
    {
        static::addGlobalScope(new AgencyScope);

        static::creating(function (Model $model): void {
            if ($model->getAttribute('agency_id') === null && Auth::check()) {
                $model->setAttribute('agency_id', Auth::user()->agency_id);
            }
        });
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }
}
