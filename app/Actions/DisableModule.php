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
 * Turn a module off for a company. What the module recorded stays in the
 * database, and the roles that grant its permissions keep the grant: turning the
 * module back on picks up where it left off, with nothing to configure again.
 */
class DisableModule
{
    public function __construct(
        private readonly User $author,
        private readonly Company $company,
        private readonly ModuleEnum $module,
    ) {}

    public function execute(): Company
    {
        $this->authorize();
        $this->disable();
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

    private function disable(): void
    {
        $settings = $this->company->settings ?? [];
        $modules = $settings['modules'] ?? [];

        $settings['modules'] = array_values(array_filter(
            $modules,
            fn (string $module): bool => $module !== $this->module->value,
        ));

        $this->company->settings = $settings;
        $this->company->save();
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            company: $this->company,
            user: $this->author,
            action: UserActionEnum::ModuleDisabled,
            parameters: ['name' => $this->module->label()],
        )->onQueue('low');
    }
}
