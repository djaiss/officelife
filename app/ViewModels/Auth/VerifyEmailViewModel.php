<?php

declare(strict_types=1);

namespace App\ViewModels\Auth;

class VerifyEmailViewModel
{
    public function __construct(
        private readonly string $email,
    ) {}

    public function email(): string
    {
        return $this->email;
    }

    /**
     * One line from The Office, for the panel on the side of the screen.
     *
     * @return array{text: string, author: string, source: string}
     */
    public function quote(): array
    {
        $quotes = config('quotes');

        return $quotes[array_rand($quotes)];
    }
}
