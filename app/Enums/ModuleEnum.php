<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The parts of the product a company turns on and off. The core is not in here:
 * it is always active and cannot be disabled, which is the whole distinction
 * between the two.
 *
 * A module is stored as its value in the settings of the company. Nothing else
 * records that it is on.
 */
enum ModuleEnum: string
{
    case Assets = 'assets';

    /**
     * What the module is called on the screen where somebody turns it on. The
     * sentence doubles as the translation key.
     */
    public function label(): string
    {
        return match ($this) {
            self::Assets => 'Assets',
        };
    }

    /**
     * The line under the name, saying what turning it on gives the company.
     * The sentence doubles as the translation key.
     */
    public function note(): string
    {
        return match ($this) {
            self::Assets => 'The equipment the company owns, who holds it, and getting it back',
        };
    }
}
