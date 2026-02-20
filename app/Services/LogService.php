<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Post;
use App\Repositories\PostRepositoryInterface;
use InvalidArgumentException;

final class LogService
{
    public function __construct(private PostRepositoryInterface $posts)
    {
    }

    /** @return list<Post> */
    public function searchLogs(string $query = '', ?string $fromDate = null, ?string $toDate = null): array
    {
        $fromDate = $this->normalizeDateOrNull($fromDate, '開始日');
        $toDate = $this->normalizeDateOrNull($toDate, '終了日');

        if ($fromDate !== null && $toDate !== null && $fromDate > $toDate) {
            throw new InvalidArgumentException('開始日は終了日以前で指定してください。');
        }

        return $this->posts->search($query, $fromDate, $toDate);
    }

    /** @return list<array{date:string,count:int}> */
    public function listDownloadableDates(): array
    {
        return $this->posts->listDatesWithCounts();
    }

    public function buildDailyLogText(string $date): string
    {
        $date = $this->requireValidDate($date);
        $posts = $this->posts->findByDate($date);

        $lines = [];
        $lines[] = 'Date: ' . $date;
        $lines[] = 'Count: ' . count($posts);
        $lines[] = str_repeat('=', 60);

        foreach ($posts as $post) {
            $lines[] = sprintf('[%d] %s / %s / %s', (int) $post->id, $post->author, $post->title, $post->createdAt);
            $lines[] = $post->body;
            $lines[] = str_repeat('-', 60);
        }

        return implode(PHP_EOL, $lines) . PHP_EOL;
    }

    private function normalizeDateOrNull(?string $date, string $label): ?string
    {
        $date = trim((string) $date);
        if ($date === '') {
            return null;
        }
        if (!$this->isValidDate($date)) {
            throw new InvalidArgumentException($label . 'の形式が不正です。YYYY-MM-DD で指定してください。');
        }
        return $date;
    }

    private function requireValidDate(string $date): string
    {
        $date = trim($date);
        if (!$this->isValidDate($date)) {
            throw new InvalidArgumentException('日付の形式が不正です。YYYY-MM-DD で指定してください。');
        }
        return $date;
    }

    private function isValidDate(string $date): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }
        [$y, $m, $d] = array_map('intval', explode('-', $date));
        return checkdate($m, $d, $y);
    }
}

