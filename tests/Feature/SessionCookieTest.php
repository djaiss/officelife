<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SessionCookieTest extends TestCase
{
    #[Test]
    public function it_does_not_mark_the_session_cookie_secure_when_the_application_is_reached_over_http(): void
    {
        $response = $this->get(route('auth.signIn.new'));

        $cookie = $response->getCookie((string) config('session.cookie'));

        $this->assertNotNull($cookie);
        $this->assertFalse($cookie->isSecure());
    }

    #[Test]
    public function it_marks_the_session_cookie_secure_the_same_way_whichever_scheme_answers(): void
    {
        $overHttp = $this->get('http://localhost'.route('auth.signIn.new', absolute: false))
            ->getCookie((string) config('session.cookie'));

        $overHttps = $this->get('https://localhost'.route('auth.signIn.new', absolute: false))
            ->getCookie((string) config('session.cookie'));

        $this->assertNotNull($overHttp);
        $this->assertNotNull($overHttps);
        $this->assertSame($overHttp->isSecure(), $overHttps->isSecure());
    }
}
