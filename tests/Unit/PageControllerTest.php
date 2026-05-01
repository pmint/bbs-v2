<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Controllers\PageController;
use App\Services\PostService;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryPostRepository;

final class PageControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
        $_GET = [];
        $_POST = [];
        $_SERVER['REQUEST_URI'] = '/press';
    }

    public function testPressShowsRoadmap(): void
    {
        $controller = new PageController(new PostService(new InMemoryPostRepository()));

        ob_start();
        $controller->press();
        $html = (string) ob_get_clean();

        self::assertStringContainsString('今後の予定', $html);
        self::assertStringContainsString('過去ログ検索の条件を見直しやすくしました。', $html);
        self::assertStringContainsString('投稿前の確認をしやすくしました。', $html);
        self::assertStringContainsString('通知体験の整理を行いました。', $html);
        self::assertStringContainsString('通知体験の整理', $html);
        self::assertStringContainsString('投稿入力の補助を小さく試す', $html);
        self::assertStringContainsString('検索条件の再利用を検討する', $html);
        self::assertStringContainsString('広報室と運用導線を整える', $html);
    }
}
