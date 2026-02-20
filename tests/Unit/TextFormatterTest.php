<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\TextFormatter;
use PHPUnit\Framework\TestCase;

final class TextFormatterTest extends TestCase
{
    public function testLinkifyUrlsCreatesAnchorWithBlankTarget(): void
    {
        $input = 'see https://example.com/path?a=1&b=2 now';

        $actual = TextFormatter::linkifyUrls($input);

        self::assertStringContainsString('target="_blank"', $actual);
        self::assertStringContainsString('rel="noopener noreferrer"', $actual);
        self::assertStringContainsString('<a href="https://example.com/path?a=1&amp;b=2"', $actual);
    }

    public function testLinkifyUrlsEscapesHtmlBeforeLinkifying(): void
    {
        $input = '<script>alert(1)</script> https://example.com';

        $actual = TextFormatter::linkifyUrls($input);

        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $actual);
        self::assertStringNotContainsString('<script>', $actual);
        self::assertStringContainsString('<a href="https://example.com"', $actual);
    }

    public function testLinkifyUrlsAlsoLinkifiesHashtags(): void
    {
        $input = 'tag #php and ＃タグ';

        $actual = TextFormatter::linkifyUrls($input);

        self::assertStringContainsString('?q=%23php', $actual);
        self::assertStringContainsString('?q=%23%E3%82%BF%E3%82%B0', $actual);
    }

    public function testHashInUrlIsNotTreatedAsStandaloneHashtag(): void
    {
        $input = 'https://example.com/#fragment #topic';

        $actual = TextFormatter::linkifyUrls($input);

        self::assertStringContainsString('<a href="https://example.com/#fragment"', $actual);
        self::assertStringContainsString('?q=%23topic', $actual);
    }
}
