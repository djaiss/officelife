<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\Preferences;

use App\Enums\TimeFormatEnum;
use App\Models\Employee;
use App\Models\User;

class PreferencesViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly ?Employee $employee,
    ) {}

    /** @return array<int, array{value: string, label: string, hint: string, selected: bool}> */
    public function locales(): array
    {
        $current = $this->locale();

        return collect(config('officelife.locales'))
            ->map(fn (array $locale, string $code): array => [
                'value' => $code,
                'label' => $locale['label'],
                'hint' => $locale['region'],
                'selected' => $code === $current,
            ])
            ->values()
            ->all();
    }

    public function localeLabel(): string
    {
        return config('officelife.locales')[$this->locale()]['label'];
    }

    /** @return array<int, array{value: string, label: string, hint: string, selected: bool}> */
    public function timeFormats(): array
    {
        return collect(TimeFormatEnum::cases())
            ->map(fn (TimeFormatEnum $format): array => [
                'value' => $format->value,
                'label' => __($format->label()),
                'hint' => $format->example(),
                'selected' => $format === $this->timeFormat(),
            ])
            ->all();
    }

    public function timeFormatLabel(): string
    {
        return __($this->timeFormat()->label());
    }

    public function timePreview(): string
    {
        return $this->timeFormat()->format(now());
    }

    public function name(): string
    {
        return $this->employee->name ?? $this->user->email;
    }

    public function employee(): ?Employee
    {
        return $this->employee;
    }

    public function companyName(): string
    {
        return $this->user->company->name;
    }

    public function locale(): string
    {
        $locale = app()->getLocale();

        return array_key_exists($locale, config('officelife.locales'))
            ? $locale
            : config('app.locale');
    }

    public function timeFormat(): TimeFormatEnum
    {
        return $this->user->time_format ?? TimeFormatEnum::TwentyFourHour;
    }
}
