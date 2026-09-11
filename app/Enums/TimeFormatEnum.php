<?php

declare(strict_types=1);

namespace App\Enums;

use Carbon\CarbonInterface;

enum TimeFormatEnum: string
{
    case TwentyFourHour = '24';
    case TwelveHour = '12';

    public function label(): string
    {
        return match ($this) {
            self::TwentyFourHour => '24-hour',
            self::TwelveHour => '12-hour',
        };
    }

    public function example(): string
    {
        return $this->format(now()->setTime(14, 0));
    }

    public function format(CarbonInterface $time): string
    {
        return match ($this) {
            self::TwentyFourHour => $time->format('H:i'),
            self::TwelveHour => $time->format('g:i A'),
        };
    }
}
