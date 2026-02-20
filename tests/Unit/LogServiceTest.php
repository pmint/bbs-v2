<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\LogService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryPostRepository;

final class LogServiceTest extends TestCase
{
    public function testSearchLogsByKeyword(): void
    {
        $repo = new InMemoryPostRepository();
        $repo->createWithDate('alice', 'foo', 'hello world', '2026-02-16 10:00:00');
        $repo->createWithDate('bob', 'bar', 'other', '2026-02-17 11:00:00');

        $service = new LogService($repo);
        $result = $service->searchLogs('hello');

        self::assertCount(1, $result);
        self::assertSame('alice', $result[0]->author);
    }

    public function testSearchLogsByDateRange(): void
    {
        $repo = new InMemoryPostRepository();
        $repo->createWithDate('alice', 'foo', 'A', '2026-02-16 10:00:00');
        $repo->createWithDate('bob', 'bar', 'B', '2026-02-17 10:00:00');
        $repo->createWithDate('carol', 'baz', 'C', '2026-02-18 10:00:00');

        $service = new LogService($repo);
        $result = $service->searchLogs('', '2026-02-17', '2026-02-18');

        self::assertCount(2, $result);
        self::assertSame('carol', $result[0]->author);
        self::assertSame('bob', $result[1]->author);
    }

    public function testSearchLogsRejectsInvalidDate(): void
    {
        $service = new LogService(new InMemoryPostRepository());

        $this->expectException(InvalidArgumentException::class);
        $service->searchLogs('', '2026-99-99', null);
    }

    public function testBuildDailyLogTextContainsTargetDatePosts(): void
    {
        $repo = new InMemoryPostRepository();
        $repo->createWithDate('alice', 'foo', 'A', '2026-02-16 10:00:00');
        $repo->createWithDate('bob', 'bar', 'B', '2026-02-17 10:00:00');

        $service = new LogService($repo);
        $text = $service->buildDailyLogText('2026-02-16');

        self::assertStringContainsString('Date: 2026-02-16', $text);
        self::assertStringContainsString('alice', $text);
        self::assertStringNotContainsString('bob', $text);
    }
}

