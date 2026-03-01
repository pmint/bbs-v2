<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Post;

final class Hashtag
{
    /**
     * @return list<string>
     */
    public static function extractTags(string $text): array
    {
        $normalized = str_replace('＃', '#', $text);
        $matched = preg_match_all('/#([\p{L}\p{N}_]+)/u', $normalized, $captures);
        if (!is_int($matched) || $matched <= 0 || !isset($captures[1]) || !is_array($captures[1])) {
            return [];
        }

        $tags = [];
        foreach ($captures[1] as $tag) {
            if (!is_string($tag)) {
                continue;
            }
            $tag = trim($tag);
            if ($tag === '') {
                continue;
            }
            $tags[] = $tag;
        }

        return array_values(array_unique($tags));
    }

    /**
     * @param list<Post> $posts
     * @return list<array{tag:string,count:int}>
     */
    public static function buildTagCounts(array $posts, int $limit = 15): array
    {
        $counts = [];
        foreach ($posts as $post) {
            $tags = self::extractTags($post->title . "\n" . $post->body);
            foreach ($tags as $tag) {
                $counts[$tag] = ($counts[$tag] ?? 0) + 1;
            }
        }

        if ($counts === []) {
            return [];
        }

        $items = [];
        foreach ($counts as $tag => $count) {
            $items[] = [
                'tag' => (string) $tag,
                'count' => (int) $count,
            ];
        }

        usort(
            $items,
            static function (array $a, array $b): int {
                if ($a['count'] !== $b['count']) {
                    return $b['count'] <=> $a['count'];
                }
                return strcmp((string) $a['tag'], (string) $b['tag']);
            }
        );

        return array_slice($items, 0, max(0, $limit));
    }
}
