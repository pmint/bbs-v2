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
    }
}
