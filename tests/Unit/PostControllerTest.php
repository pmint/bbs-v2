<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Controllers\PostController;
use App\Services\PostService;
use App\Support\Url;
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

    public function testIndexShowsUnreadReplyBarAndSummaryNoticeForRepliesToOwnedPosts(): void
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
        self::assertStringContainsString('未読返信があります。', $html);
        self::assertStringNotContainsString('あなたの投稿「root title」に返信がありました。', $html);
        self::assertSame([(int) $reply->id], $_SESSION['notified_reply_ids']);
    }

    public function testIndexShowsPluralSummaryNoticeForMultipleNewUnreadReplies(): void
    {
        $service = new PostService(new InMemoryPostRepository());
        $controller = new PostController($service);
        $_SESSION['owner_key'] = 'owner-key';

        $root = $service->createPostWithOwnerKey('me', 'root title', 'root body', 'owner-key');
        $firstReply = $service->createPost('other', 'reply title 1', 'reply body 1', (int) $root->id);
        $secondReply = $service->createPost('other', 'reply title 2', 'reply body 2', (int) $root->id);

        ob_start();
        $controller->index();
        $html = (string) ob_get_clean();

        self::assertStringContainsString('未読返信 2件', $html);
        self::assertStringContainsString('未読返信が2件あります。', $html);
        self::assertSame([(int) $secondReply->id, (int) $firstReply->id], $_SESSION['notified_reply_ids']);
    }

    public function testIndexDoesNotRepeatSummaryNoticeForAlreadyNotifiedUnreadReply(): void
    {
        $service = new PostService(new InMemoryPostRepository());
        $controller = new PostController($service);
        $_SESSION['owner_key'] = 'owner-key';

        $root = $service->createPostWithOwnerKey('me', 'root title', 'root body', 'owner-key');
        $service->createPost('other', 'reply title', 'reply body', (int) $root->id);

        ob_start();
        $controller->index();
        ob_end_clean();

        ob_start();
        $controller->index();
        $html = (string) ob_get_clean();

        self::assertStringContainsString('未読返信 1件', $html);
        self::assertStringNotContainsString('未読返信があります。', $html);
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
        self::assertStringNotContainsString('未読返信があります。', $html);
        self::assertArrayNotHasKey('notified_reply_ids', $_SESSION);
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

    public function testIndexDoesNotNotifyForSelfReply(): void
    {
        $service = new PostService(new InMemoryPostRepository());
        $controller = new PostController($service);
        $_SESSION['owner_key'] = 'owner-key';

        $root = $service->createPostWithOwnerKey('me', 'root title', 'root body', 'owner-key');
        $service->createPostWithOwnerKey('me', 'self reply', 'self body', 'owner-key', (int) $root->id);

        ob_start();
        $controller->index();
        $html = (string) ob_get_clean();

        self::assertStringNotContainsString('未読返信 1件', $html);
        self::assertStringNotContainsString('未読返信があります。', $html);
        self::assertArrayNotHasKey('notified_reply_ids', $_SESSION);
    }

    public function testCreatePrefillsTitleWithFilterKeywordWhenFilteringIsActive(): void
    {
        $service = new PostService(new InMemoryPostRepository());
        $controller = new PostController($service);
        $_SESSION['post_filter_query'] = '#意見';

        ob_start();
        $controller->create();
        $html = (string) ob_get_clean();

        self::assertStringContainsString('id="title"', $html);
        self::assertStringContainsString('value="#意見 "', $html);
        self::assertStringContainsString('<textarea id="body" name="body" rows="8"></textarea>', $html);
        self::assertStringContainsString('data-draft-form', $html);
        self::assertStringContainsString('data-draft-key="bbs-v2:create"', $html);
        self::assertStringContainsString('data-preview-toggle', $html);
        self::assertStringContainsString('data-draft-clear', $html);
        self::assertStringContainsString('投稿前確認', $html);
        self::assertStringContainsString('下書きはこのブラウザに残ります。', $html);
    }

    public function testEditShowsDraftAndPreviewTools(): void
    {
        $service = new PostService(new InMemoryPostRepository());
        $controller = new PostController($service);
        $_SESSION['owner_key'] = 'owner-key';

        $post = $service->createPostWithOwnerKey('me', 'edit title', 'edit body', 'owner-key');

        ob_start();
        $controller->edit(['id' => (string) $post->id]);
        $html = (string) ob_get_clean();

        self::assertStringContainsString('data-draft-form', $html);
        self::assertStringContainsString('data-draft-key="bbs-v2:edit:' . (int) $post->id . '"', $html);
        self::assertStringContainsString('data-preview-toggle', $html);
        self::assertStringContainsString('data-draft-clear', $html);
        self::assertStringContainsString('投稿前確認', $html);
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

    public function testIndexRightAlignsOwnPostAndReplyToOwnPost(): void
    {
        $service = new PostService(new InMemoryPostRepository());
        $controller = new PostController($service);
        $_SESSION['owner_key'] = 'owner-key';

        $myPost = $service->createPostWithOwnerKey('me', 'my title', 'my body', 'owner-key');
        $reply = $service->createPost('other', 'reply title', 'reply body', (int) $myPost->id);

        ob_start();
        $controller->index();
        $html = (string) ob_get_clean();

        self::assertStringContainsString('id="post-' . (int) $myPost->id . '"', $html);
        self::assertStringContainsString('class="card is-own" id="post-' . (int) $myPost->id . '"', $html);
        self::assertStringContainsString('class="card is-reply-to-own" id="post-' . (int) $reply->id . '"', $html);
    }

    public function testThreadRightAlignsOwnPostAndReplyToOwnPost(): void
    {
        $service = new PostService(new InMemoryPostRepository());
        $controller = new PostController($service);
        $_SESSION['owner_key'] = 'owner-key';

        $myPost = $service->createPostWithOwnerKey('me', 'thread root', 'root body', 'owner-key');
        $reply = $service->createPost('other', 'thread reply', 'reply body', (int) $myPost->id);

        ob_start();
        $controller->thread(['id' => (string) $myPost->id]);
        $html = (string) ob_get_clean();

        self::assertStringContainsString('class="card is-own" id="post-' . (int) $myPost->id . '"', $html);
        self::assertStringContainsString('class="card is-reply-to-own" id="post-' . (int) $reply->id . '"', $html);
    }

    public function testSanitizeRedirectPathRejectsSchemeRelativeUrl(): void
    {
        $controller = new PostController(new PostService(new InMemoryPostRepository()));
        $method = new \ReflectionMethod($controller, 'sanitizeRedirectPath');

        $actual = $method->invoke($controller, '//evil.example/phish');

        self::assertSame(Url::to('/posts'), $actual);
    }

    public function testSanitizeRedirectPathKeepsLocalPathWithQueryAndFragment(): void
    {
        $controller = new PostController(new PostService(new InMemoryPostRepository()));
        $method = new \ReflectionMethod($controller, 'sanitizeRedirectPath');

        $actual = $method->invoke($controller, '/posts/thread/1?from=logs#post-2');

        self::assertSame('/posts/thread/1?from=logs#post-2', $actual);
    }
}
