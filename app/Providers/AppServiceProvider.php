<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\AssetAssigneeTypeEnum;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->registerMorphMap();
    }

    // The short names are written into polymorphic columns, so changing one
    // orphans every row already holding it.
    private function registerMorphMap(): void
    {
        Relation::morphMap([
            AssetAssigneeTypeEnum::Employee->value => AssetAssigneeTypeEnum::Employee->model(),
            AssetAssigneeTypeEnum::Office->value => AssetAssigneeTypeEnum::Office->model(),
            AssetAssigneeTypeEnum::Asset->value => AssetAssigneeTypeEnum::Asset->model(),
        ]);
    }
}
