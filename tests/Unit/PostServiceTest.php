<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\PostService;
use App\Support\AuthorEmoji;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Fakes\InMemoryPostRepository;

final class PostServiceTest extends TestCase
{
    public function testCreatePostSuccess(): void
    {
        $service = new PostService(new InMemoryPostRepository());
        $post = $service->createPost('alice', 'hello', 'world');

        self::assertSame('alice', $post->author);
        self::assertSame('hello', $post->title);
        self::assertSame('world', $post->body);
    }

    public function testCreatePostValidationFailsWhenBodyIsEmpty(): void
    {
        $service = new PostService(new InMemoryPostRepository());

        $this->expectException(InvalidArgumentException::class);
        $service->createPost('', '', '');
    }

    public function testUpdatePostSuccess(): void
    {
        $service = new PostService(new InMemoryPostRepository());
        $ownerKey = 'owner-key';
        $post = $service->createPostWithOwnerKey('alice', 'hello', 'world', $ownerKey);

        $updated = $service->updatePost((int) $post->id, 'bob', 'updated', 'new body', $ownerKey);
        self::assertSame('bob', $updated->author);
        self::assertSame('updated', $updated->title);
    }

    public function testUpdatePostFailsWhenNotFound(): void
    {
        $service = new PostService(new InMemoryPostRepository());

        $this->expectException(RuntimeException::class);
        $service->updatePost(999, 'bob', 'updated', 'new body', 'owner-key');
    }

    public function testDeletePostSuccess(): void
    {
        $service = new PostService(new InMemoryPostRepository());
        $ownerKey = 'owner-key';
        $post = $service->createPostWithOwnerKey('alice', 'hello', 'world', $ownerKey);

        $service->deletePost((int) $post->id, $ownerKey);

        $this->expectException(RuntimeException::class);
        $service->getPost((int) $post->id);
    }

    public function testUpdatePostFailsWhenOwnerKeyDoesNotMatch(): void
    {
        $service = new PostService(new InMemoryPostRepository());
        $post = $service->createPostWithOwnerKey('alice', 'hello', 'world', 'owner-key');

        $this->expectException(RuntimeException::class);
        $service->updatePost((int) $post->id, 'bob', 'updated', 'new body', 'other-key');
    }

    public function testDeletePostFailsWhenOwnerKeyDoesNotMatch(): void
    {
        $service = new PostService(new InMemoryPostRepository());
        $post = $service->createPostWithOwnerKey('alice', 'hello', 'world', 'owner-key');

        $this->expectException(RuntimeException::class);
        $service->deletePost((int) $post->id, 'other-key');
    }

    public function testCanModifyPostReturnsFalseWhenOwnerKeyMissing(): void
    {
        $service = new PostService(new InMemoryPostRepository());
        $post = $service->createPost('alice', 'hello', 'world');

        self::assertFalse($service->canModifyPost((int) $post->id, 'owner-key'));
    }

    public function testCreateReplySetsThreadAndParent(): void
    {
        $service = new PostService(new InMemoryPostRepository());
        $root = $service->createPost('alice', 'root', 'root body');
        $reply = $service->createPost('bob', 'reply', 'reply body', (int) $root->id);

        self::assertSame($root->id, $reply->parentId);
        self::assertSame($root->threadId, $reply->threadId);
    }

    public function testListThreadPostsReturnsPostsInThread(): void
    {
        $service = new PostService(new InMemoryPostRepository());
        $root = $service->createPost('alice', 'root', 'root body');
        $service->createPost('bob', 'reply1', 'reply body1', (int) $root->id);
        $service->createPost('carol', 'reply2', 'reply body2', (int) $root->id);

        $threadPosts = $service->listThreadPosts((int) $root->threadId);

        self::assertCount(3, $threadPosts);
    }

    public function testListPostsByQueryReturnsAllWhenQueryIsEmpty(): void
    {
        $service = new PostService(new InMemoryPostRepository());
        $service->createPost('alice', 'hello', 'world');
        $service->createPost('bob', 'topic', 'body');

        $posts = $service->listPostsByQuery('   ');

        self::assertCount(2, $posts);
    }

    public function testListPostsByQueryFindsByAuthorTitleAndBody(): void
    {
        $service = new PostService(new InMemoryPostRepository());
        $service->createPost('alice', 'hello', 'world');
        $service->createPost('bob', 'keyword title', 'zzz');
        $service->createPost('charlie', 'other', 'contains keyword');

        $posts = $service->listPostsByQuery('keyword');

        self::assertCount(2, $posts);
    }

    public function testToggleLikeIncrementsAndDecrements(): void
    {
        $service = new PostService(new InMemoryPostRepository());
        $post = $service->createPost('alice', 'hello', 'world');

        $liked = $service->toggleLike((int) $post->id, false);
        self::assertSame(1, $liked->likeCount);

        $unliked = $service->toggleLike((int) $post->id, true);
        self::assertSame(0, $unliked->likeCount);
    }

    public function testToggleLikeNeverGoesBelowZero(): void
    {
        $service = new PostService(new InMemoryPostRepository());
        $post = $service->createPost('alice', 'hello', 'world');

        $unliked = $service->toggleLike((int) $post->id, true);
        self::assertSame(0, $unliked->likeCount);
    }

    public function testListRecentPostsReturnsOnlyWithinDaysRange(): void
    {
        $repo = new InMemoryPostRepository();
        $service = new PostService($repo);
        $repo->createWithDate('alice', 'old', '#old', date('Y-m-d H:i:s', strtotime('-8 days')));
        $repo->createWithDate('bob', 'new', '#new', date('Y-m-d H:i:s', strtotime('-6 days')));
        $repo->createWithDate('carol', 'today', '#today', date('Y-m-d H:i:s'));

        $posts = $service->listRecentPosts(7);

        self::assertCount(2, $posts);
        self::assertSame('today', $posts[0]->title);
        self::assertSame('new', $posts[1]->title);
    }

    public function testAuthorWithSecretIsStoredAsNameAndTwoEmoji(): void
    {
        $service = new PostService(new InMemoryPostRepository());
        $post = $service->createPost('alice#secret', 'hello', 'world');

        self::assertSame('alice ' . AuthorEmoji::fromSecret('secret'), $post->author);
        self::assertTrue($post->authorIsGenerated);
    }

    public function testAuthorWithoutSecretIsStoredAsIs(): void
    {
        $service = new PostService(new InMemoryPostRepository());
        $post = $service->createPost('alice', 'hello', 'world');

        self::assertSame('alice', $post->author);
        self::assertFalse($post->authorIsGenerated);
    }

    public function testAuthorWithEmptySecretIsStoredAsPlainName(): void
    {
        $service = new PostService(new InMemoryPostRepository());
        $post = $service->createPost('alice#', 'hello', 'world');

        self::assertSame('alice', $post->author);
        self::assertFalse($post->authorIsGenerated);
    }

    public function testAuthorWithOnlySecretIsStoredAsTwoEmoji(): void
    {
        $service = new PostService(new InMemoryPostRepository());
        $post = $service->createPost('#secret', 'hello', 'world');

        self::assertSame(AuthorEmoji::fromSecret('secret'), $post->author);
        self::assertTrue($post->authorIsGenerated);
    }
}
