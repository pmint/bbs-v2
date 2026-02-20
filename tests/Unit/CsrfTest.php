<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Csrf;
use PHPUnit\Framework\TestCase;

final class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
    }

    public function testTokenIsGeneratedAndVerifiable(): void
    {
        $token = Csrf::token();
        self::assertNotSame('', $token);
        self::assertTrue(Csrf::verify($token));
    }

    public function testVerifyReturnsFalseOnInvalidToken(): void
    {
        Csrf::token();
        self::assertFalse(Csrf::verify('invalid'));
    }
}

