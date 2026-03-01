<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Controllers\PostController;
use App\Services\PostService;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryPostRepository;

final class PostControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
        $_GET = [];
        $_POST = [];
        $_SERVER['REQUEST_URI'] = '/posts';
    }

    public function testIndexShowsUnreadReplyBarForRepliesToOwnedPosts(): void
    {
        $service = new PostService(new InMemoryPostRepository());
        $controller = new PostController($service);
        $_SESSION['owner_key'] = 'owner-key';

        $root = $service->createPostWithOwnerKey('me', 'root title', 'root body', 'owner-key');
        $reply = $service->createPost('other', 'reply title', 'reply body', (int) $root->id);
        $service->createPostWithOwnerKey('me', 'self reply', 'self body', 'owner-key', (int) $root->id);

        ob_start();
        $controller->index();
        $html = (string) ob_get_clean();

        self::assertStringContainsString('未読返信 1件', $html);
        self::assertStringContainsString('mark_read_reply_id=' . (int) $reply->id . '#post-' . (int) $reply->id, $html);
    }

    public function testIndexMarksReplyAsReadWhenMarkReadReplyIdIsPassed(): void
    {
        $service = new PostService(new InMemoryPostRepository());
        $controller = new PostController($service);
        $_SESSION['owner_key'] = 'owner-key';

        $root = $service->createPostWithOwnerKey('me', 'root title', 'root body', 'owner-key');
        $reply = $service->createPost('other', 'reply title', 'reply body', (int) $root->id);
        $_GET['mark_read_reply_id'] = (string) $reply->id;

        ob_start();
        $controller->index();
        $html = (string) ob_get_clean();

        self::assertSame([(int) $reply->id], $_SESSION['read_reply_ids']);
        self::assertStringNotContainsString('未読返信 1件', $html);
    }

    public function testIndexIgnoresInvalidMarkReadReplyId(): void
    {
        $service = new PostService(new InMemoryPostRepository());
        $controller = new PostController($service);
        $_SESSION['owner_key'] = 'owner-key';

        $root = $service->createPostWithOwnerKey('me', 'root title', 'root body', 'owner-key');
        $service->createPost('other', 'reply title', 'reply body', (int) $root->id);
        $_GET['mark_read_reply_id'] = 'invalid';

        ob_start();
        $controller->index();
        ob_end_clean();

        self::assertArrayNotHasKey('read_reply_ids', $_SESSION);
    }

    public function testCreatePrefillsBodyWithFilterKeywordWhenFilteringIsActive(): void
    {
        $service = new PostService(new InMemoryPostRepository());
        $controller = new PostController($service);
        $_SESSION['post_filter_query'] = '#意見';

        ob_start();
        $controller->create();
        $html = (string) ob_get_clean();
        $decoded = str_replace("\r\n", "\n", html_entity_decode($html, ENT_QUOTES, 'UTF-8'));

        self::assertStringContainsString('<textarea id="body" name="body" rows="8">', $html);
        self::assertStringContainsString("\n #意見 \n</textarea>", $decoded);
    }

    public function testIndexRendersHashtagLinksInPostTitle(): void
    {
        $service = new PostService(new InMemoryPostRepository());
        $controller = new PostController($service);
        $service->createPost('alice', '#要望 タイトル', '本文');

        ob_start();
        $controller->index();
        $html = (string) ob_get_clean();

        self::assertStringContainsString('?q=%23%E8%A6%81%E6%9C%9B', $html);
    }
}
