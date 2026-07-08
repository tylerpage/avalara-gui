<?php

namespace App\Models\Concerns;

use App\Services\DashboardContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToDashboard
{
    protected static function bootBelongsToDashboard(): void
    {
        static::addGlobalScope('dashboard', function (Builder $builder): void {
            if (! app()->bound(DashboardContext::class)) {
                return;
            }

            $builder->where(
                $builder->getModel()->getTable().'.dashboard_id',
                app(DashboardContext::class)->id(),
            );
        });

        static::creating(function (Model $model): void {
            if ($model->getAttribute('dashboard_id') !== null || ! app()->bound(DashboardContext::class)) {
                return;
            }

            $model->setAttribute('dashboard_id', app(DashboardContext::class)->id());
        });
    }
}
