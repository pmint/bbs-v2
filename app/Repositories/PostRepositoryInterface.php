<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Post;

interface PostRepositoryInterface
{
    /** @return list<Post> */
    public function all(): array;

    /** @return list<Post> */
    public function search(string $query = '', ?string $fromDate = null, ?string $toDate = null): array;

    /** @return list<array{date:string,count:int}> */
    public function listDatesWithCounts(): array;

    /** @return list<Post> */
    public function findByDate(string $date): array;

    /** @return list<Post> */
    public function findThreadPosts(int $threadId): array;

    public function findById(int $id): ?Post;

    public function create(
        string $author,
        string $title,
        string $body,
        ?int $parentId = null,
        ?int $threadId = null,
        ?string $ownerKeyHash = null
    ): Post;

    public function isOwnedBy(int $id, string $ownerKeyHash): bool;

    public function update(int $id, string $author, string $title, string $body, string $ownerKeyHash): ?Post;

    public function delete(int $id, string $ownerKeyHash): bool;

    public function toggleLike(int $id, bool $liked): ?Post;
}
