<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Post;
use App\Services\PostService;
use App\Support\View;

final class PageController
{
    public function __construct(private PostService $service)
    {
    }

    public function press(): void
    {
        $allPosts = $this->service->listPosts();
        $feedbackPosts = $this->collectFeedbackPosts($allPosts, 12);

        View::render('pages/press', [
            'title' => '広報室',
            'updates' => $this->updates(),
            'feedbackPosts' => $feedbackPosts,
        ]);
    }

    /**
     * @param list<Post> $posts
     * @return list<Post>
     */
    private function collectFeedbackPosts(array $posts, int $limit): array
    {
        $result = [];
        foreach ($posts as $post) {
            $haystack = $post->title . "\n" . $post->body;
            if (mb_stripos($haystack, '#意見') === false && mb_stripos($haystack, '#要望') === false) {
                continue;
            }
            $result[] = $post;
            if (count($result) >= $limit) {
                break;
            }
        }
        return $result;
    }

    /**
     * @return list<array{date:string,text:string}>
     */
    private function updates(): array
    {
        return [
            ['date' => '2026-02-27', 'text' => '掲示板トップの表示範囲を「最新100件 + 直近1か月（和集合）」に変更しました。'],
            ['date' => '2026-02-27', 'text' => '過去ログは条件未指定時に0件表示、条件指定時は無制限検索に変更しました。'],
            ['date' => '2026-02-26', 'text' => '投稿者名の #秘密 から絵文字2個を生成する表示に対応しました。'],
            ['date' => '2026-02-26', 'text' => '未読返信バーとジャンプ導線を追加しました。'],
            ['date' => '2026-02-26', 'text' => '広報室の #意見 / #要望 リンクを絞り込み付きに更新しました。'],
        ];
    }
}
