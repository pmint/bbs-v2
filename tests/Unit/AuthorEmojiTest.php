<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\AuthorEmoji;
use PHPUnit\Framework\TestCase;

final class AuthorEmojiTest extends TestCase
{
    public function testSameSecretReturnsSameEmojiPair(): void
    {
        $a = AuthorEmoji::fromSecret('secret-value');
        $b = AuthorEmoji::fromSecret('secret-value');

        self::assertSame($a, $b);
    }

    public function testEmptySecretReturnsEmptyString(): void
    {
        self::assertSame('', AuthorEmoji::fromSecret(''));
        self::assertSame('', AuthorEmoji::fromSecret('   '));
    }

    public function testSecretReturnsTwoEmojiCharacters(): void
    {
        $emoji = AuthorEmoji::fromSecret('another-secret');
        self::assertNotSame('', $emoji);
        self::assertSame(AuthorEmoji::fromSecret('another-secret'), $emoji);
        self::assertStringNotContainsString('#', $emoji);
    }
}
