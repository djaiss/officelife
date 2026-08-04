<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\AssetAssigneeTypeEnum;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerMorphMap();
    }

    /**
     * Say which short name stands for which model in a polymorphic column.
     *
     * An asset assignment stores what is holding the equipment as one of the
     * cases of AssetAssigneeTypeEnum, so the short name has to resolve back to a
     * model. The map is not enforced, because a domain event stores the class
     * name of whatever it is about, which can be any model at all.
     */
    private function registerMorphMap(): void
    {
        Relation::morphMap([
            AssetAssigneeTypeEnum::Employee->value => AssetAssigneeTypeEnum::Employee->model(),
            AssetAssigneeTypeEnum::Location->value => AssetAssigneeTypeEnum::Location->model(),
            AssetAssigneeTypeEnum::Asset->value => AssetAssigneeTypeEnum::Asset->model(),
        ]);
    }
}
