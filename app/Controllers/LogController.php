<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\LogService;
use App\Support\Csrf;
use App\Support\View;
use InvalidArgumentException;

final class LogController
{
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

        if ($this->hasSearchCriteria($query, $fromDate, $toDate)) {
            try {
                $results = $this->service->searchLogs($query, $fromDate, $toDate);
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
            'results' => $results,
            'dateList' => $this->service->listDownloadableDates(),
            'likedMap' => $this->buildLikedMap($results),
            'csrfToken' => Csrf::token(),
        ]);
    }

    public function download(): void
    {
        $date = (string) ($_GET['date'] ?? '');

        try {
            $text = $this->service->buildDailyLogText($date);
        } catch (InvalidArgumentException $e) {
            http_response_code(400);
            header('Content-Type: text/plain; charset=UTF-8');
            echo $e->getMessage();
            return;
        }

        header('Content-Type: text/plain; charset=UTF-8');
        header('Content-Disposition: attachment; filename="posts-' . $date . '.txt"');
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
}
