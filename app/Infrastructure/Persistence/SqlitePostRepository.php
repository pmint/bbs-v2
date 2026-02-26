<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Models\Post;
use App\Repositories\PostRepositoryInterface;
use PDO;

final class SqlitePostRepository implements PostRepositoryInterface
{
    private PDO $pdo;

    public function __construct(string $databasePath)
    {
        $dir = dirname($databasePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $this->pdo = new PDO('sqlite:' . $databasePath);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->initSchema();
    }

    public function all(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, author, title, body, created_at, parent_id, thread_id, owner_key_hash, like_count, author_is_generated FROM posts ORDER BY id DESC'
        );
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->mapRowsToPosts($rows);
    }

    public function search(string $query = '', ?string $fromDate = null, ?string $toDate = null): array
    {
        $sql = 'SELECT id, author, title, body, created_at, parent_id, thread_id, owner_key_hash, like_count, author_is_generated FROM posts WHERE 1=1';
        $params = [];

        $query = trim($query);
        if ($query !== '') {
            $sql .= ' AND (author LIKE :q OR title LIKE :q OR body LIKE :q)';
            $params[':q'] = '%' . $query . '%';
        }
        if ($fromDate !== null && $fromDate !== '') {
            $sql .= ' AND date(created_at) >= :from_date';
            $params[':from_date'] = $fromDate;
        }
        if ($toDate !== null && $toDate !== '') {
            $sql .= ' AND date(created_at) <= :to_date';
            $params[':to_date'] = $toDate;
        }
        $sql .= ' ORDER BY id DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->mapRowsToPosts($rows);
    }

    public function listDatesWithCounts(): array
    {
        $stmt = $this->pdo->query(
            'SELECT date(created_at) AS log_date, COUNT(*) AS cnt
             FROM posts
             GROUP BY date(created_at)
             ORDER BY log_date DESC'
        );
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $result[] = [
                'date' => (string) $row['log_date'],
                'count' => (int) $row['cnt'],
            ];
        }
        return $result ?? [];
    }

    public function findByDate(string $date): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, author, title, body, created_at, parent_id, thread_id, owner_key_hash, like_count, author_is_generated
             FROM posts
             WHERE date(created_at) = :log_date
             ORDER BY id ASC'
        );
        $stmt->execute([':log_date' => $date]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->mapRowsToPosts($rows);
    }

    public function findThreadPosts(int $threadId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, author, title, body, created_at, parent_id, thread_id, owner_key_hash, like_count, author_is_generated
             FROM posts
             WHERE thread_id = :thread_id
             ORDER BY id ASC'
        );
        $stmt->execute([':thread_id' => $threadId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->mapRowsToPosts($rows);
    }

    public function findById(int $id): ?Post
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, author, title, body, created_at, parent_id, thread_id, owner_key_hash, like_count, author_is_generated FROM posts WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }

        return new Post(
            (int) $row['id'],
            (string) $row['author'],
            (string) $row['title'],
            (string) $row['body'],
            (string) $row['created_at'],
            isset($row['parent_id']) ? ($row['parent_id'] !== null ? (int) $row['parent_id'] : null) : null,
            isset($row['thread_id']) ? ($row['thread_id'] !== null ? (int) $row['thread_id'] : null) : null,
            isset($row['owner_key_hash']) ? ($row['owner_key_hash'] !== null ? (string) $row['owner_key_hash'] : null) : null,
            isset($row['like_count']) ? (int) $row['like_count'] : 0,
            isset($row['author_is_generated']) ? (int) $row['author_is_generated'] === 1 : false
        );
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
        $createdAt = date('Y-m-d H:i:s');

        $stmt = $this->pdo->prepare(
            'INSERT INTO posts (author, title, body, created_at, parent_id, thread_id, owner_key_hash, like_count, author_is_generated)
             VALUES (:author, :title, :body, :created_at, :parent_id, :thread_id, :owner_key_hash, :like_count, :author_is_generated)'
        );
        $stmt->execute([
            ':author' => $author,
            ':title' => $title,
            ':body' => $body,
            ':created_at' => $createdAt,
            ':parent_id' => $parentId,
            ':thread_id' => $threadId,
            ':owner_key_hash' => $ownerKeyHash,
            ':like_count' => 0,
            ':author_is_generated' => $authorIsGenerated ? 1 : 0,
        ]);

        $newId = (int) $this->pdo->lastInsertId();
        if ($threadId === null) {
            $threadId = $newId;
            $updateThread = $this->pdo->prepare('UPDATE posts SET thread_id = :thread_id WHERE id = :id');
            $updateThread->execute([
                ':thread_id' => $threadId,
                ':id' => $newId,
            ]);
        }

        return new Post(
            $newId,
            $author,
            $title,
            $body,
            $createdAt,
            $parentId,
            $threadId,
            $ownerKeyHash,
            0,
            $authorIsGenerated
        );
    }

    public function isOwnedBy(int $id, string $ownerKeyHash): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM posts WHERE id = :id AND owner_key_hash = :owner_key_hash'
        );
        $stmt->execute([
            ':id' => $id,
            ':owner_key_hash' => $ownerKeyHash,
        ]);
        return (int) $stmt->fetchColumn() > 0;
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
        $stmt = $this->pdo->prepare(
            'UPDATE posts
             SET author = :author, title = :title, body = :body, author_is_generated = :author_is_generated
             WHERE id = :id AND owner_key_hash = :owner_key_hash'
        );
        $stmt->execute([
            ':id' => $id,
            ':author' => $author,
            ':title' => $title,
            ':body' => $body,
            ':owner_key_hash' => $ownerKeyHash,
            ':author_is_generated' => $authorIsGenerated ? 1 : 0,
        ]);
        if ($stmt->rowCount() === 0) {
            return null;
        }

        return $this->findById($id);
    }

    public function delete(int $id, string $ownerKeyHash): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM posts WHERE id = :id AND owner_key_hash = :owner_key_hash');
        $stmt->execute([
            ':id' => $id,
            ':owner_key_hash' => $ownerKeyHash,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function toggleLike(int $id, bool $liked): ?Post
    {
        if ($liked) {
            $stmt = $this->pdo->prepare(
                'UPDATE posts
                 SET like_count = CASE WHEN like_count > 0 THEN like_count - 1 ELSE 0 END
                 WHERE id = :id'
            );
        } else {
            $stmt = $this->pdo->prepare(
                'UPDATE posts
                 SET like_count = like_count + 1
                 WHERE id = :id'
            );
        }
        $stmt->execute([':id' => $id]);
        if ($stmt->rowCount() === 0) {
            return null;
        }
        return $this->findById($id);
    }

    private function initSchema(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                author TEXT NOT NULL,
                title TEXT NOT NULL,
                body TEXT NOT NULL,
                created_at TEXT NOT NULL,
                parent_id INTEGER NULL,
                thread_id INTEGER NULL,
                owner_key_hash TEXT NULL,
                like_count INTEGER NOT NULL DEFAULT 0,
                author_is_generated INTEGER NOT NULL DEFAULT 0
            )'
        );
        $this->ensureColumnExists('parent_id', 'INTEGER NULL');
        $this->ensureColumnExists('thread_id', 'INTEGER NULL');
        $this->ensureColumnExists('owner_key_hash', 'TEXT NULL');
        $this->ensureColumnExists('like_count', 'INTEGER NOT NULL DEFAULT 0');
        $this->ensureColumnExists('author_is_generated', 'INTEGER NOT NULL DEFAULT 0');
        $this->pdo->exec('UPDATE posts SET thread_id = id WHERE thread_id IS NULL');
        $this->pdo->exec('UPDATE posts SET like_count = 0 WHERE like_count IS NULL');
        $this->pdo->exec('UPDATE posts SET author_is_generated = 0 WHERE author_is_generated IS NULL');
    }

    /** @param list<array<string,mixed>> $rows
     *  @return list<Post>
     */
    private function mapRowsToPosts(array $rows): array
    {
        $posts = [];
        foreach ($rows as $row) {
            $posts[] = new Post(
                (int) $row['id'],
                (string) $row['author'],
                (string) $row['title'],
                (string) $row['body'],
                (string) $row['created_at'],
                isset($row['parent_id']) ? ($row['parent_id'] !== null ? (int) $row['parent_id'] : null) : null,
                isset($row['thread_id']) ? ($row['thread_id'] !== null ? (int) $row['thread_id'] : null) : null,
                isset($row['owner_key_hash']) ? ($row['owner_key_hash'] !== null ? (string) $row['owner_key_hash'] : null) : null,
                isset($row['like_count']) ? (int) $row['like_count'] : 0,
                isset($row['author_is_generated']) ? (int) $row['author_is_generated'] === 1 : false
            );
        }
        return $posts;
    }

    private function ensureColumnExists(string $columnName, string $columnDefinition): void
    {
        $stmt = $this->pdo->query('PRAGMA table_info(posts)');
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $column) {
            if (($column['name'] ?? '') === $columnName) {
                return;
            }
        }
        $this->pdo->exec('ALTER TABLE posts ADD COLUMN ' . $columnName . ' ' . $columnDefinition);
    }
}
