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
        self::assertStringContainsString('通知体験の整理を行いました。', $html);
        self::assertStringContainsString('通知体験の整理', $html);
        self::assertStringContainsString('投稿前の確認をしやすくする', $html);
        self::assertStringContainsString('探しやすさを広げる', $html);
        self::assertStringContainsString('入力補助を検討する', $html);
    }
}
