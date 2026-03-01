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
            ['date' => '2026-02-27', 'text' => '過去ログダウンロードを月別（YYYY-MM）に変更。使い方: 過去ログページの「月別ログダウンロード」から対象月を選んで保存してください。'],
            ['date' => '2026-02-27', 'text' => '掲示板トップの表示範囲を「最新100件 + 直近1か月（和集合）」に変更。使い方: 古い投稿が見えない場合は過去ログ検索を使って探してください。'],
            ['date' => '2026-02-27', 'text' => '過去ログは条件未指定時に0件表示、条件指定時は無制限検索に変更。使い方: 検索語または開始日/終了日を入れて検索してください。'],
            ['date' => '2026-02-26', 'text' => '投稿者名の #秘密 から絵文字2個を生成する「絵文字トリップ」に対応。使い方: 例「しば#ひみつ」と入力すると表示名は「しば + 絵文字2個」になります。'],
            ['date' => '2026-02-26', 'text' => '未読返信バーとジャンプ導線を追加。使い方: 一覧上部の未読リンクを押すと該当返信へ移動し、既読にできます。'],
            ['date' => '2026-02-26', 'text' => '広報室の #意見 / #要望 リンクを絞り込み付きに更新。使い方: 広報室からリンクを開くと関連投稿だけ確認できます。'],
        ];
    }
}
