<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Post;
use App\Support\Hashtag;
use PHPUnit\Framework\TestCase;

final class HashtagTest extends TestCase
{
    public function testExtractTagsSupportsHalfAndFullWidthHash(): void
    {
        $tags = Hashtag::extractTags('test #php ＃タグ #tag_1');

        self::assertSame(['php', 'タグ', 'tag_1'], $tags);
    }

    public function testExtractTagsIgnoresEmptyOrInvalid(): void
    {
        $tags = Hashtag::extractTags('# #! #,');

        self::assertSame([], $tags);
    }

    public function testBuildTagCountsAggregatesAndLimits(): void
    {
        $posts = [];
        for ($i = 1; $i <= 20; $i++) {
            $posts[] = new Post(
                $i,
                'a',
                't',
                '#tag' . $i . ' #common',
                '2026-01-01 00:00:00'
            );
        }

        $result = Hashtag::buildTagCounts($posts, 15);

        self::assertCount(15, $result);
        self::assertSame('common', $result[0]['tag']);
        self::assertSame(20, $result[0]['count']);
    }
}

