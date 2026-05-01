<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Controllers\LogController;
use App\Services\LogService;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryPostRepository;

final class LogControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
        $_GET = [];
        $_POST = [];
        $_SERVER['REQUEST_URI'] = '/logs';
    }

    public function testIndexShowsZeroResultsWhenNoSearchCriteriaIsProvided(): void
    {
        $repo = new InMemoryPostRepository();
        $repo->createWithDate('alice', 'alpha-title', 'body', '2026-02-20 10:00:00');
        $controller = new LogController(new LogService($repo));

        ob_start();
        $controller->index();
        $html = (string) ob_get_clean();

        self::assertStringNotContainsString('alpha-title', $html);
        self::assertStringNotContainsString('該当する投稿はありません。', $html);
    }

    public function testIndexRunsSearchWhenQueryIsProvided(): void
    {
        $repo = new InMemoryPostRepository();
        $repo->createWithDate('alice', 'needle-title', 'needle body', '2026-02-20 10:00:00');
        $repo->createWithDate('bob', 'other-title', 'other body', '2026-02-21 10:00:00');
        $controller = new LogController(new LogService($repo));
        $_GET['q'] = 'needle';

        ob_start();
        $controller->index();
        $html = (string) ob_get_clean();

        self::assertStringContainsString('needle-title', $html);
        self::assertStringNotContainsString('other-title', $html);
        self::assertStringNotContainsString('該当する投稿はありません。', $html);
        self::assertStringContainsString('検索条件:', $html);
        self::assertStringContainsString('検索語「needle」', $html);
        self::assertStringContainsString('検索結果: 1件', $html);
        self::assertStringContainsString('検索語だけ解除', $html);
        self::assertStringContainsString('条件をすべて解除', $html);
    }

    public function testIndexShowsSearchSummaryAndNoResultMessageForDateRange(): void
    {
        $repo = new InMemoryPostRepository();
        $repo->createWithDate('alice', 'old-title', 'old body', '2026-02-20 10:00:00');
        $controller = new LogController(new LogService($repo));
        $_GET['from'] = '2026-03-01';
        $_GET['to'] = '2026-03-31';

        ob_start();
        $controller->index();
        $html = (string) ob_get_clean();

        self::assertStringContainsString('開始日 2026-03-01', $html);
        self::assertStringContainsString('終了日 2026-03-31', $html);
        self::assertStringContainsString('検索結果: 0件', $html);
        self::assertStringContainsString('開始日だけ解除', $html);
        self::assertStringContainsString('終了日だけ解除', $html);
        self::assertStringContainsString('該当する投稿はありません。', $html);
        self::assertStringNotContainsString('old-title', $html);
    }

    public function testIndexShowsMonthSearchLink(): void
    {
        $repo = new InMemoryPostRepository();
        $repo->createWithDate('alice', 'month-title', 'body', '2026-02-20 10:00:00');
        $controller = new LogController(new LogService($repo));

        ob_start();
        $controller->index();
        $html = (string) ob_get_clean();

        self::assertStringContainsString('この月で検索', $html);
        self::assertStringContainsString('from=2026-02-01&amp;to=2026-02-28', $html);
    }
}
