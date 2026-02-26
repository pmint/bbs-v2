<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Post;
use App\Repositories\PostRepositoryInterface;
use App\Support\AuthorEmoji;
use InvalidArgumentException;
use RuntimeException;

final class PostService
{
    public function __construct(private PostRepositoryInterface $posts)
    {
    }

    /** @return list<Post> */
    public function listPosts(): array
    {
        return $this->posts->all();
    }

    /** @return list<Post> */
    public function listPostsByQuery(string $query): array
    {
        $query = trim($query);
        if ($query === '') {
            return $this->posts->all();
        }
        return $this->posts->search($query, null, null);
    }

    /** @return list<Post> */
    public function listRecentPosts(int $days = 7): array
    {
        $days = max(1, $days);
        $toDate = date('Y-m-d');
        $fromDate = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
        return $this->posts->search('', $fromDate, $toDate);
    }

    /** @return list<Post> */
    public function listThreadPosts(int $threadId): array
    {
        if ($threadId <= 0) {
            throw new RuntimeException('不正なスレッドIDです。');
        }
        $posts = $this->posts->findThreadPosts($threadId);
        if ($posts === []) {
            throw new RuntimeException('スレッドが見つかりません。');
        }
        return $posts;
    }

    public function createPost(string $author, string $title, string $body, ?int $replyToId = null): Post
    {
        return $this->createPostWithOwnerKey($author, $title, $body, null, $replyToId);
    }

    public function createPostWithOwnerKey(
        string $author,
        string $title,
        string $body,
        ?string $ownerKey,
        ?int $replyToId = null
    ): Post
    {
        [$author, $title, $body] = $this->validateFields($author, $title, $body);
        [$author, $authorIsGenerated] = $this->normalizeAuthorForDisplay($author);
        $parentId = null;
        $threadId = null;

        if ($replyToId !== null) {
            if ($replyToId <= 0) {
                throw new InvalidArgumentException('返信先IDが不正です。');
            }
            $replyTo = $this->posts->findById($replyToId);
            if ($replyTo === null) {
                throw new RuntimeException('返信先の投稿が見つかりません。');
            }
            $parentId = (int) $replyTo->id;
            $threadId = $replyTo->threadId ?? (int) $replyTo->id;
        }

        $ownerKeyHash = $ownerKey !== null && $ownerKey !== '' ? $this->hashOwnerKey($ownerKey) : null;
        return $this->posts->create($author, $title, $body, $parentId, $threadId, $ownerKeyHash, $authorIsGenerated);
    }

    public function canModifyPost(int $id, string $ownerKey): bool
    {
        $post = $this->posts->findById($id);
        if ($post === null || $post->ownerKeyHash === null || $ownerKey === '') {
            return false;
        }
        return $this->posts->isOwnedBy($id, $this->hashOwnerKey($ownerKey));
    }

    public function getPost(int $id): Post
    {
        $post = $this->posts->findById($id);
        if ($post === null) {
            throw new RuntimeException('投稿が見つかりません。');
        }
        return $post;
    }

    public function updatePost(int $id, string $author, string $title, string $body, string $ownerKey): Post
    {
        $existing = $this->posts->findById($id);
        if ($existing === null) {
            throw new RuntimeException('投稿が見つかりません。');
        }
        if ($ownerKey === '' || !$this->posts->isOwnedBy($id, $this->hashOwnerKey($ownerKey))) {
            throw new RuntimeException('この投稿は現在のセッションでは編集できません。');
        }

        [$author, $title, $body] = $this->validateFields($author, $title, $body);
        [$author, $authorIsGenerated] = $this->normalizeAuthorForDisplay($author);
        $post = $this->posts->update($id, $author, $title, $body, $this->hashOwnerKey($ownerKey), $authorIsGenerated);
        if ($post === null) {
            throw new RuntimeException('投稿の更新に失敗しました。');
        }
        return $post;
    }

    public function deletePost(int $id, string $ownerKey): void
    {
        $existing = $this->posts->findById($id);
        if ($existing === null) {
            throw new RuntimeException('投稿が見つかりません。');
        }
        if ($ownerKey === '' || !$this->posts->isOwnedBy($id, $this->hashOwnerKey($ownerKey))) {
            throw new RuntimeException('この投稿は現在のセッションでは削除できません。');
        }
        if (!$this->posts->delete($id, $this->hashOwnerKey($ownerKey))) {
            throw new RuntimeException('投稿の削除に失敗しました。');
        }
    }

    public function toggleLike(int $id, bool $liked): Post
    {
        if ($id <= 0) {
            throw new RuntimeException('不正な投稿IDです。');
        }
        $post = $this->posts->toggleLike($id, $liked);
        if ($post === null) {
            throw new RuntimeException('投稿が見つかりません。');
        }
        return $post;
    }

    /** @return array{0:string,1:string,2:string} */
    private function validateFields(string $author, string $title, string $body): array
    {
        $author = trim($author);
        $title = trim($title);
        $body = trim($body);

        if ($body === '') {
            throw new InvalidArgumentException('本文は必須です。');
        }

        return [$author, $title, $body];
    }

    /** @return array{0:string,1:bool} */
    private function normalizeAuthorForDisplay(string $author): array
    {
        $hashPos = mb_strpos($author, '#');
        if ($hashPos === false) {
            return [$author, false];
        }

        $displayName = trim(mb_substr($author, 0, $hashPos));
        $secret = trim(mb_substr($author, $hashPos + 1));
        if ($secret === '') {
            return [$displayName, false];
        }

        $emoji = AuthorEmoji::fromSecret($secret);
        if ($displayName === '') {
            return [$emoji, true];
        }
        return [$displayName . ' ' . $emoji, true];
    }

    private function hashOwnerKey(string $ownerKey): string
    {
        return hash('sha256', $ownerKey);
    }
}
