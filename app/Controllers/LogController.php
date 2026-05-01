<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\LogService;
use App\Support\Csrf;
use App\Support\View;
use InvalidArgumentException;

final class LogController
{
    private const RECENT_LOG_SEARCH_LIMIT = 5;

    public function __construct(private LogService $service)
    {
    }

    public function index(): void
    {
        $query = (string) ($_GET['q'] ?? '');
        $fromDate = (string) ($_GET['from'] ?? '');
        $toDate = (string) ($_GET['to'] ?? '');
        $errors = [];
        $results = [];
        $hasSearchCriteria = $this->hasSearchCriteria($query, $fromDate, $toDate);

        if ($hasSearchCriteria) {
            try {
                $results = $this->service->searchLogs($query, $fromDate, $toDate);
                $this->rememberSearchCriteria($query, $fromDate, $toDate);
            } catch (InvalidArgumentException $e) {
                $errors[] = $e->getMessage();
            }
        }

        View::render('logs/index', [
            'title' => '過去ログ検索',
            'errors' => $errors,
            'query' => $query,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'hasSearchCriteria' => $hasSearchCriteria,
            'resultCount' => count($results),
            'recentSearches' => $this->getRecentLogSearches(),
            'results' => $results,
            'monthList' => $this->service->listDownloadableMonths(),
            'likedMap' => $this->buildLikedMap($results),
            'csrfToken' => Csrf::token(),
        ]);
    }

    public function download(): void
    {
        $month = (string) ($_GET['month'] ?? '');

        try {
            $text = $this->service->buildMonthlyLogText($month);
        } catch (InvalidArgumentException $e) {
            http_response_code(400);
            header('Content-Type: text/plain; charset=UTF-8');
            echo $e->getMessage();
            return;
        }

        header('Content-Type: text/plain; charset=UTF-8');
        header('Content-Disposition: attachment; filename="posts-' . $month . '.txt"');
        echo $text;
    }

    /** @param list<\App\Models\Post> $posts
     *  @return array<int,bool>
     */
    private function buildLikedMap(array $posts): array
    {
        $raw = $_SESSION['liked_post_ids'] ?? [];
        $likedIds = [];
        if (is_array($raw)) {
            foreach ($raw as $id) {
                $id = (int) $id;
                if ($id > 0) {
                    $likedIds[$id] = true;
                }
            }
        }

        $map = [];
        foreach ($posts as $post) {
            if ($post->id === null) {
                continue;
            }
            $id = (int) $post->id;
            $map[$id] = isset($likedIds[$id]);
        }
        return $map;
    }

    private function hasSearchCriteria(string $query, string $fromDate, string $toDate): bool
    {
        return trim($query) !== '' || trim($fromDate) !== '' || trim($toDate) !== '';
    }

    private function rememberSearchCriteria(string $query, string $fromDate, string $toDate): void
    {
        $entry = $this->normalizeSearchCriteria($query, $fromDate, $toDate);
        if ($entry === null) {
            return;
        }

        $recent = [];
        foreach ($this->getRecentLogSearches() as $item) {
            if ($item === $entry) {
                continue;
            }
            $recent[] = $item;
        }
        array_unshift($recent, $entry);

        $_SESSION['recent_log_searches'] = array_slice($recent, 0, self::RECENT_LOG_SEARCH_LIMIT);
    }

    /**
     * @return list<array{q:string,from:string,to:string}>
     */
    private function getRecentLogSearches(): array
    {
        $raw = $_SESSION['recent_log_searches'] ?? [];
        if (!is_array($raw)) {
            return [];
        }

        $recent = [];
        foreach ($raw as $item) {
            if (!is_array($item)) {
                continue;
            }
            $entry = $this->normalizeSearchCriteria(
                (string) ($item['q'] ?? ''),
                (string) ($item['from'] ?? ''),
                (string) ($item['to'] ?? '')
            );
            if ($entry === null || in_array($entry, $recent, true)) {
                continue;
            }
            $recent[] = $entry;
            if (count($recent) >= self::RECENT_LOG_SEARCH_LIMIT) {
                break;
            }
        }

        return $recent;
    }

    /**
     * @return array{q:string,from:string,to:string}|null
     */
    private function normalizeSearchCriteria(string $query, string $fromDate, string $toDate): ?array
    {
        $entry = [
            'q' => trim($query),
            'from' => trim($fromDate),
            'to' => trim($toDate),
        ];

        if (!$this->hasSearchCriteria($entry['q'], $entry['from'], $entry['to'])) {
            return null;
        }

        return $entry;
    }
}
