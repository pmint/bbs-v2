<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Url;
use PHPUnit\Framework\TestCase;

final class UrlTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUrlConfig(['url_mode' => 'index_php']);
        $_SERVER['SCRIPT_NAME'] = '/cgi-bin/bbs-v2/public/index.php';
    }

    protected function tearDown(): void
    {
        $this->resetUrlConfig();
        parent::tearDown();
    }

    public function testToUsesIndexPhpModeForRootAndSubPath(): void
    {
        self::assertSame('/cgi-bin/bbs-v2/public/index.php', Url::to('/'));
        self::assertSame('/cgi-bin/bbs-v2/public/index.php/posts', Url::to('posts'));
    }

    public function testToUsesRewriteModeWithoutIndexPhp(): void
    {
        $this->setUrlConfig(['url_mode' => 'rewrite']);

        self::assertSame('/cgi-bin/bbs-v2/public/', Url::to('/'));
        self::assertSame('/cgi-bin/bbs-v2/public/posts', Url::to('/posts'));
    }

    public function testToDefaultsToRewriteModeWhenUrlModeIsMissing(): void
    {
        $this->setUrlConfig([]);

        self::assertSame('/cgi-bin/bbs-v2/public/posts', Url::to('/posts'));
    }

    public function testToReturnsRootWhenPathIsEmptyInRewriteMode(): void
    {
        $this->setUrlConfig(['url_mode' => 'rewrite']);

        self::assertSame('/cgi-bin/bbs-v2/public/', Url::to('  '));
    }

    public function testBasePathReturnsEmptyForRootScript(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        self::assertSame('', Url::basePath());
        self::assertSame('/index.php', Url::to('/'));
    }

    public function testToTrimsWhitespaceInPath(): void
    {
        self::assertSame('/cgi-bin/bbs-v2/public/index.php/posts', Url::to('  posts  '));
    }

    private function setUrlConfig(array $config): void
    {
        $property = new \ReflectionProperty(Url::class, 'config');
        $property->setValue(null, $config);
    }

    private function resetUrlConfig(): void
    {
        $property = new \ReflectionProperty(Url::class, 'config');
        $property->setValue(null, null);
    }
}
