<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The sections the permissions are laid out in on the screen where somebody
 * grants them. It is a way of reading a long list, and nothing else: a group
 * grants nothing and is never stored.
 */
enum PermissionGroupEnum: string
{
    case People = 'people';
    case SensitiveData = 'sensitive_data';
    case Administration = 'administration';

    /**
     * What the group is called. The sentence doubles as the translation key.
     */
    public function label(): string
    {
        return match ($this) {
            self::People => 'People',
            self::SensitiveData => 'Sensitive data',
            self::Administration => 'Administration',
        };
    }

    /**
     * The line beside the group title, saying what the permissions under it
     * have in common. The sentence doubles as the translation key.
     */
    public function note(): string
    {
        return match ($this) {
            self::People => 'The employee record itself',
            self::SensitiveData => 'Fields kept off the screen for anybody not allowed to see them',
            self::Administration => 'The company and the way access to it is handed out',
        };
    }
}
