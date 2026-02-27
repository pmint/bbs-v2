<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Models\Post;
use App\Repositories\PostRepositoryInterface;

final class InMemoryPostRepository implements PostRepositoryInterface
{
    /** @var array<int, Post> */
    private array $posts = [];
    private int $nextId = 1;

    public function all(): array
    {
        $posts = array_values($this->posts);
        usort($posts, static fn(Post $a, Post $b): int => ($b->id ?? 0) <=> ($a->id ?? 0));
        return $posts;
    }

    public function searchInDisplayScope(
        string $query = '',
        ?string $fromDate = null,
        ?string $toDate = null,
        string $scopeStartDate = '',
        int $latestLimit = 100
    ): array {
        $all = $this->all();
        $latestLimit = max(1, $latestLimit);
        $scopeStartDate = $scopeStartDate === '' ? date('Y-m-d') : $scopeStartDate;

        $latestIds = [];
        foreach (array_slice($all, 0, $latestLimit) as $post) {
            if ($post->id !== null) {
                $latestIds[(int) $post->id] = true;
            }
        }

        $result = [];
        foreach ($all as $post) {
            $id = (int) ($post->id ?? 0);
            $date = substr($post->createdAt, 0, 10);

            $inScope = isset($latestIds[$id]) || $date >= $scopeStartDate;
            if (!$inScope) {
                continue;
            }
            if ($fromDate !== null && $fromDate !== '' && $date < $fromDate) {
                continue;
            }
            if ($toDate !== null && $toDate !== '' && $date > $toDate) {
                continue;
            }
            if ($query !== '') {
                $haystack = $post->author . ' ' . $post->title . ' ' . $post->body;
                if (mb_stripos($haystack, $query) === false) {
                    continue;
                }
            }
            $result[] = $post;
        }
        return $result;
    }

    public function search(string $query = '', ?string $fromDate = null, ?string $toDate = null): array
    {
        $result = [];
        foreach ($this->all() as $post) {
            $date = substr($post->createdAt, 0, 10);
            if ($fromDate !== null && $fromDate !== '' && $date < $fromDate) {
                continue;
            }
            if ($toDate !== null && $toDate !== '' && $date > $toDate) {
                continue;
            }
            if ($query !== '') {
                $haystack = $post->author . ' ' . $post->title . ' ' . $post->body;
                if (mb_stripos($haystack, $query) === false) {
                    continue;
                }
            }
            $result[] = $post;
        }
        return $result;
    }

    public function listDatesWithCounts(): array
    {
        $counts = [];
        foreach ($this->posts as $post) {
            $date = substr($post->createdAt, 0, 10);
            $counts[$date] = ($counts[$date] ?? 0) + 1;
        }
        krsort($counts);

        $result = [];
        foreach ($counts as $date => $count) {
            $result[] = ['date' => $date, 'count' => $count];
        }
        return $result;
    }

    public function findByDate(string $date): array
    {
        $result = [];
        foreach ($this->posts as $post) {
            if (substr($post->createdAt, 0, 10) === $date) {
                $result[] = $post;
            }
        }
        usort($result, static fn(Post $a, Post $b): int => ($a->id ?? 0) <=> ($b->id ?? 0));
        return $result;
    }

    public function findThreadPosts(int $threadId): array
    {
        $result = [];
        foreach ($this->posts as $post) {
            if (($post->threadId ?? $post->id) === $threadId) {
                $result[] = $post;
            }
        }
        usort($result, static fn(Post $a, Post $b): int => ($a->id ?? 0) <=> ($b->id ?? 0));
        return $result;
    }

    public function findById(int $id): ?Post
    {
        return $this->posts[$id] ?? null;
    }

    public function create(
        string $author,
        string $title,
        string $body,
        ?int $parentId = null,
        ?int $threadId = null,
        ?string $ownerKeyHash = null,
        bool $authorIsGenerated = false
    ): Post
    {
        $id = $this->nextId;
        $threadId = $threadId ?? $id;
        $post = new Post(
            $id,
            $author,
            $title,
            $body,
            date('Y-m-d H:i:s'),
            $parentId,
            $threadId,
            $ownerKeyHash,
            0,
            $authorIsGenerated
        );
        $this->posts[$this->nextId] = $post;
        $this->nextId++;
        return $post;
    }

    public function createWithDate(
        string $author,
        string $title,
        string $body,
        string $createdAt,
        ?int $parentId = null,
        ?int $threadId = null,
        ?string $ownerKeyHash = null,
        int $likeCount = 0,
        bool $authorIsGenerated = false
    ): Post
    {
        $id = $this->nextId;
        $threadId = $threadId ?? $id;
        $post = new Post($id, $author, $title, $body, $createdAt, $parentId, $threadId, $ownerKeyHash, $likeCount, $authorIsGenerated);
        $this->posts[$this->nextId] = $post;
        $this->nextId++;
        return $post;
    }

    public function isOwnedBy(int $id, string $ownerKeyHash): bool
    {
        $post = $this->posts[$id] ?? null;
        if ($post === null || $post->ownerKeyHash === null) {
            return false;
        }
        return hash_equals($post->ownerKeyHash, $ownerKeyHash);
    }

    public function update(
        int $id,
        string $author,
        string $title,
        string $body,
        string $ownerKeyHash,
        bool $authorIsGenerated = false
    ): ?Post
    {
        if (!isset($this->posts[$id]) || !$this->isOwnedBy($id, $ownerKeyHash)) {
            return null;
        }
        $post = $this->posts[$id];
        $updated = new Post(
            $id,
            $author,
            $title,
            $body,
            $post->createdAt,
            $post->parentId,
            $post->threadId,
            $post->ownerKeyHash,
            $post->likeCount,
            $authorIsGenerated
        );
        $this->posts[$id] = $updated;
        return $updated;
    }

    public function delete(int $id, string $ownerKeyHash): bool
    {
        if (!isset($this->posts[$id]) || !$this->isOwnedBy($id, $ownerKeyHash)) {
            return false;
        }
        unset($this->posts[$id]);
        return true;
    }

    public function toggleLike(int $id, bool $liked): ?Post
    {
        if (!isset($this->posts[$id])) {
            return null;
        }
        $post = $this->posts[$id];
        $count = $post->likeCount;
        if ($liked) {
            $count = max(0, $count - 1);
        } else {
            $count++;
        }
        $updated = new Post(
            $post->id,
            $post->author,
            $post->title,
            $post->body,
            $post->createdAt,
            $post->parentId,
            $post->threadId,
            $post->ownerKeyHash,
            $count,
            $post->authorIsGenerated
        );
        $this->posts[$id] = $updated;
        return $updated;
    }
}
