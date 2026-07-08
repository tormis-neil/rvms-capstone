<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Global scope enforcing agency-level data isolation (FR-02, NFR-02):
 * every query on a scoped model is restricted to the authenticated
 * user's own agency. Unauthenticated contexts (console commands,
 * seeders, scheduled jobs) are not filtered.
 */
class AgencyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if ($user !== null) {
            $builder->where(
                $model->qualifyColumn('agency_id'),
                $user->agency_id,
            );
        }
    }
}
