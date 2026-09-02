<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Tenancy\CurrentCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Filters every query on a tenant-owned model by the current company.
 *
 * If no tenant is resolved (console, unauthenticated) the scope is a no-op so
 * seeders and jobs still work; HTTP always resolves one via middleware.
 *
 * @implements Scope<Model>
 */
final class CompanyScope implements Scope
{
    /**
     * @param  Builder<*>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        $current = app(CurrentCompany::class);

        if (! $current->isResolved()) {
            return;
        }

        $builder->where($model->qualifyColumn('company_id'), $current->id());
    }
}
