<?php

declare(strict_types=1);

namespace App\ViewModels\Settings;

use App\Enums\PermissionEnum;
use App\Enums\TimeFormatEnum;
use App\Models\Employee;
use App\Models\User;

/**
 * The way into every setting: what somebody can change about their own account,
 * and what they administer for the company they work at.
 *
 * Each row carries the value it currently holds, so that the screen answers the
 * question the row asks without anybody having to open it.
 */
class SettingsViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly ?Employee $employee,
    ) {}

    public function companyName(): string
    {
        return $this->user->company->name;
    }

    /**
     * The name to show and to draw initials from. Somebody whose account is not
     * attached to an employee record has only an email address to go by.
     */
    public function name(): string
    {
        return $this->employee->name ?? $this->user->email;
    }

    /**
     * The record the avatar draws from, so the card can show it when
     * there is one. An account that belongs to nobody who works here has none.
     */
    public function employee(): ?Employee
    {
        return $this->employee;
    }

    /**
     * What somebody is, under their name on the card: the title they were hired
     * under, and the role that decides what they may do. Either can be missing,
     * and the card then shows the other alone.
     */
    public function role(): string
    {
        return collect([$this->employee?->custom_title, $this->user->roles->first()?->name])
            ->filter()
            ->implode(' · ');
    }

    /**
     * The settings of the account itself, in the order they are shown.
     *
     * @return array<int, array{title: string, description: string, value: string, url: string, hue: int, icon: string}>
     */
    public function accountRows(): array
    {
        return [
            [
                'title' => __('Profile'),
                'description' => __('Your name, avatar, work email and emergency contact.'),
                'value' => '',
                'url' => route('settings.profile.index'),
                'hue' => 30,
                'icon' => 'profile',
            ],
            [
                'title' => __('Security and access'),
                'description' => __('Your password, your devices and your API keys.'),
                'value' => $this->user->usesTwoFactorAuthentication() ? __('Two-factor on') : __('Two-factor off'),
                'url' => route('settings.security.index'),
                'hue' => 150,
                'icon' => 'security',
            ],
            [
                'title' => __('Preferences'),
                'description' => __('Language, dates, numbers and the clock.'),
                'value' => $this->preferencesValue(),
                'url' => route('settings.preferences.index'),
                'hue' => 310,
                'icon' => 'preferences',
            ],
            [
                'title' => __('Logs'),
                'description' => __('Everything you have done in :app, with dates.', ['app' => config('app.name')]),
                'value' => trans_choice(':count entry|:count entries', $this->user->logs()->count()),
                'url' => route('settings.logs.index'),
                'hue' => 200,
                'icon' => 'logs',
            ],
        ];
    }

    /**
     * The settings of the company, which only somebody allowed to change them
     * is offered. The section itself is left out when this is empty.
     *
     * @return array<int, array{title: string, description: string, value: string, url: string, hue: int, icon: string}>
     */
    public function companyRows(): array
    {
        $rows = [];

        if ($this->canManageCompany()) {
            $rows[] = [
                'title' => __('Offices'),
                'description' => __('The offices your company works from, and who sits where.'),
                'value' => trans_choice(':count office|:count offices', $this->user->company->offices()->count()),
                'url' => route('settings.offices.index'),
                'hue' => 80,
                'icon' => 'offices',
            ];
        }

        if ($this->canManageRoles()) {
            $rows[] = [
                'title' => __('Roles and permissions'),
                'description' => __('Who can see and change what across the company.'),
                'value' => $this->rolesValue(),
                'url' => route('settings.roles.index'),
                'hue' => 20,
                'icon' => 'roles',
            ];
        }

        return $rows;
    }

    /**
     * Whether the company section offers the offices of the company.
     */
    public function canManageCompany(): bool
    {
        return $this->user
            ->permission(PermissionEnum::CompanyManage)
            ->forCompany($this->user->company)
            ->allowed();
    }

    /**
     * Whether the company section offers the roles of the company.
     */
    public function canManageRoles(): bool
    {
        return $this->user
            ->permission(PermissionEnum::RoleManage)
            ->forCompany($this->user->company)
            ->allowed();
    }

    /**
     * The language the interface is in and the clock it writes times on, which
     * is everything the preferences screen holds.
     */
    private function preferencesValue(): string
    {
        $locale = app()->getLocale();

        if (! array_key_exists($locale, config('officelife.locales'))) {
            $locale = config('app.locale');
        }

        $timeFormat = $this->user->time_format ?? TimeFormatEnum::TwentyFourHour;

        return config('officelife.locales')[$locale]['label'].' · '.__($timeFormat->label());
    }

    /**
     * How many roles the company has, and how many people they are shared out
     * between.
     */
    private function rolesValue(): string
    {
        $roles = trans_choice(':count role|:count roles', $this->user->company->roles()->count());
        $people = trans_choice(':count person|:count people', $this->user->company->users()->count());

        return $roles.' · '.$people;
    }
}
