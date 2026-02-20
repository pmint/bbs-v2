<?php

declare(strict_types=1);

namespace App\Models;

final class Post
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $author,
        public readonly string $title,
        public readonly string $body,
        public readonly string $createdAt,
        public readonly ?int $parentId = null,
        public readonly ?int $threadId = null,
        public readonly ?string $ownerKeyHash = null,
        public readonly int $likeCount = 0
    ) {
    }
}
