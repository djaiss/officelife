<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Which employees a granted permission covers. A role grants one scope per
 * permission, and the scopes of several roles add up.
 *
 * Scopes for reporting lines and teams belong here once those exist, and not
 * before: a scope nothing can evaluate is a scope that quietly allows too much.
 */
enum ScopeEnum: string
{
    case Self = 'self';
    case Company = 'company';

    /**
     * What the scope is called on the screen where somebody picks it. The
     * sentence doubles as the translation key.
     */
    public function label(): string
    {
        return match ($this) {
            self::Self => 'Themselves only',
            self::Company => 'Everybody in the company',
        };
    }
}
