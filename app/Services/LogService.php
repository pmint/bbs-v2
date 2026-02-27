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

    /** @return list<array{month:string,count:int}> */
    public function listDownloadableMonths(): array
    {
        $daily = $this->posts->listDatesWithCounts();
        $monthly = [];
        foreach ($daily as $item) {
            $date = (string) ($item['date'] ?? '');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                continue;
            }
            $month = substr($date, 0, 7);
            $monthly[$month] = ($monthly[$month] ?? 0) + (int) ($item['count'] ?? 0);
        }
        krsort($monthly, SORT_STRING);

        $result = [];
        foreach ($monthly as $month => $count) {
            $result[] = ['month' => $month, 'count' => $count];
        }
        return $result;
    }

    public function buildMonthlyLogText(string $month): string
    {
        $month = $this->requireValidMonth($month);
        $fromDate = $month . '-01';
        $toDate = date('Y-m-t', strtotime($fromDate));
        $posts = $this->posts->search('', $fromDate, $toDate);

        $lines = [];
        $lines[] = 'Month: ' . $month;
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

    private function requireValidMonth(string $month): string
    {
        $month = trim($month);
        if (!$this->isValidMonth($month)) {
            throw new InvalidArgumentException('月の形式が不正です。YYYY-MM で指定してください。');
        }
        return $month;
    }

    private function isValidDate(string $date): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return false;
        }
        [$y, $m, $d] = array_map('intval', explode('-', $date));
        return checkdate($m, $d, $y);
    }

    private function isValidMonth(string $month): bool
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            return false;
        }
        [$y, $m] = array_map('intval', explode('-', $month));
        return checkdate($m, 1, $y);
    }
}
