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

    public function testListDownloadableMonthsAggregatesCountsByMonth(): void
    {
        $repo = new InMemoryPostRepository();
        $repo->createWithDate('alice', 'foo', 'A', '2026-02-16 10:00:00');
        $repo->createWithDate('bob', 'bar', 'B', '2026-02-17 10:00:00');
        $repo->createWithDate('carol', 'baz', 'C', '2026-01-17 10:00:00');

        $service = new LogService($repo);
        $months = $service->listDownloadableMonths();

        self::assertSame('2026-02', $months[0]['month']);
        self::assertSame(2, $months[0]['count']);
        self::assertSame('2026-01', $months[1]['month']);
        self::assertSame(1, $months[1]['count']);
    }

    public function testBuildMonthlyLogTextContainsTargetMonthPosts(): void
    {
        $repo = new InMemoryPostRepository();
        $repo->createWithDate('alice', 'foo', 'A', '2026-02-16 10:00:00');
        $repo->createWithDate('bob', 'bar', 'B', '2026-02-17 10:00:00');
        $repo->createWithDate('carol', 'baz', 'C', '2026-01-18 10:00:00');

        $service = new LogService($repo);
        $text = $service->buildMonthlyLogText('2026-02');

        self::assertStringContainsString('Month: 2026-02', $text);
        self::assertStringContainsString('alice', $text);
        self::assertStringContainsString('bob', $text);
        self::assertStringNotContainsString('carol', $text);
    }

    public function testBuildMonthlyLogTextRejectsInvalidMonth(): void
    {
        $service = new LogService(new InMemoryPostRepository());

        $this->expectException(InvalidArgumentException::class);
        $service->buildMonthlyLogText('2026-99');
    }
}
