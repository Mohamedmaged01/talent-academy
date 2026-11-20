<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * `Model::forAdminBranch()` limits a query to the signed-in administrator's
 * branch when their role is branch-scoped; otherwise it changes nothing.
 * Records with no branch (e.g. an application that did not choose one) stay
 * visible to everyone.
 */
trait ScopedToBranch
{
    public function scopeForAdminBranch(Builder $query): Builder
    {
        $branch = auth()->user()?->branchScope();

        return $branch
            ? $query->where(fn (Builder $q) => $q->where($this->qualifyColumn('branch_id'), $branch)->orWhereNull($this->qualifyColumn('branch_id')))
            : $query;
    }
}
