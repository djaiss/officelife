<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ModuleEnum;
use App\Enums\PermissionEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\User;

/**
 * Turn a module on for a company. Nothing about the module exists for a company
 * that has not done this: its permissions deny and its screens are not there.
 *
 * The list of what is on lives in the settings of the company rather than in a
 * table of its own, which is enough while turning a module on is the only thing
 * a company can say about it.
 */
class EnableModule
{
    public function __construct(
        private readonly User $author,
        private readonly Company $company,
        private readonly ModuleEnum $module,
    ) {}

    public function execute(): Company
    {
        $this->authorize();
        $this->enable();
        $this->seed();
        $this->log();

        return $this->company;
    }

    /**
     * Give the module whatever a company needs before it is worth opening.
     *
     * The core knowing which module needs what is the same pragmatic coupling as
     * PermissionEnum::module(), and is revisited at the same point: the second
     * module, when a manifest earns its keep.
     */
    private function seed(): void
    {
        match ($this->module) {
            ModuleEnum::Assets => new CreateDefaultAssetCategories(company: $this->company)->execute(),
        };
    }

    private function authorize(): void
    {
        $this->author
            ->permission(PermissionEnum::CompanyManage)
            ->forCompany($this->company)
            ->authorize();
    }

    /**
     * Turning on a module that is already on changes nothing, rather than
     * listing it twice.
     */
    private function enable(): void
    {
        $settings = $this->company->settings ?? [];
        $modules = $settings['modules'] ?? [];

        if (! in_array($this->module->value, $modules, true)) {
            $modules[] = $this->module->value;
        }

        $settings['modules'] = array_values($modules);

        $this->company->settings = $settings;
        $this->company->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->company,
            user: $this->author,
            action: UserActionEnum::ModuleEnabled,
            parameters: ['name' => $this->module->label()],
        )->onQueue('low');
    }
}
