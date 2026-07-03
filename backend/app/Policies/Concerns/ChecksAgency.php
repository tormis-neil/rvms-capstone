<?php

namespace App\Policies\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Base policy helper: cross-agency access is never allowed (FR-02).
 * Module policies (vehicles, inspections, ...) build on this check.
 */
trait ChecksAgency
{
    protected function sameAgency(User $user, Model $model): bool
    {
        return (int) $user->agency_id === (int) $model->getAttribute('agency_id');
    }
}
