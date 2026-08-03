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
        $this->log();

        return $this->company;
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
