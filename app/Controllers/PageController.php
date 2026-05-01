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
            'roadmap' => $this->roadmap(),
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
     * @return list<array{date:string,change:string,usage:string}>
     */
    private function updates(): array
    {
        return [
            [
                'date' => '2026-05-02',
                'change' => '過去ログの検索条件を戻しやすくしました。',
                'usage' => '過去ログページの「最近使った検索条件」から、直近の検索条件を再利用できます。',
            ],
            [
                'date' => '2026-05-02',
                'change' => '書き込み画面でタグを入れやすくしました。',
                'usage' => 'よく使うタグのボタンから、題名や本文へタグを入れられます。',
            ],
            [
                'date' => '2026-05-02',
                'change' => '過去ログ検索の条件を見直しやすくしました。',
                'usage' => '検索条件・結果件数を確認しながら、検索語や日付だけを解除できます。',
            ],
            [
                'date' => '2026-05-02',
                'change' => '投稿前の確認をしやすくしました。',
                'usage' => '書き込み・編集画面で、入力中の下書き保存と投稿前確認を使えます。',
            ],
            [
                'date' => '2026-05-02',
                'change' => '通知体験の整理を行いました。',
                'usage' => '未読返信は一覧上部のバーで確認し、一時通知は新しい未読返信の件数だけをお知らせします。',
            ],
            [
                'date' => '2026-04-06',
                'change' => '内部処理の見直しを行いました。',
                'usage' => '動作の安定性と表示まわりの改善を反映しています。',
            ],
            [
                'date' => '2026-03-07',
                'change' => '自分の投稿を右寄せ表示にし、他ユーザーから自分への返信も右寄せで見分けやすくしました。',
                'usage' => '通常どおり投稿・返信するだけで、自分関連の投稿カードが右側に表示されます。',
            ],
            [
                'date' => '2026-03-01',
                'change' => '題名のハッシュタグ対応を追加しました（題名内 #タグ のクリック絞り込み + タグ一覧集計）。',
                'usage' => '題名に #タグ を含めて投稿すると、クリックで絞り込みでき、ハッシュタグ一覧にも反映されます。',
            ],
            [
                'date' => '2026-03-01',
                'change' => '絞り込み中の書き込みフォームで、題名欄に絞り込みキーワードを自動入力するようにしました。',
                'usage' => '絞り込み状態で「書き込み」を開くと、題名欄に「キーワード + 半角スペース」が入ります。',
            ],
            [
                'date' => '2026-03-01',
                'change' => '過去ログの投稿カードにも「■（返信）」「◆（スレッド表示）」リンクを追加しました。',
                'usage' => '過去ログ検索結果から直接、返信投稿やスレッド表示へ移動できます。',
            ],
            [
                'date' => '2026-02-27',
                'change' => '過去ログダウンロードを月別（YYYY-MM）に変更しました。',
                'usage' => '過去ログページの「月別ログダウンロード」から対象月を選んで保存してください。',
            ],
            [
                'date' => '2026-02-27',
                'change' => '掲示板トップの表示範囲を「最新100件 + 直近1か月（和集合）」に変更しました。',
                'usage' => '古い投稿が見えない場合は過去ログ検索を使って探してください。',
            ],
            [
                'date' => '2026-02-27',
                'change' => '過去ログは条件未指定時に0件表示、条件指定時は無制限検索に変更しました。',
                'usage' => '検索語または開始日/終了日を入力して検索してください。',
            ],
            [
                'date' => '2026-02-26',
                'change' => '投稿者名の #秘密 から絵文字2個を生成する「絵文字トリップ」に対応しました。',
                'usage' => '例「しば#ひみつ」と入力すると、表示名は「しば + 絵文字2個」になります。',
            ],
            [
                'date' => '2026-02-26',
                'change' => '未読返信バーとジャンプ導線を追加しました。',
                'usage' => '一覧上部の未読リンクを押すと該当返信へ移動し、既読にできます。',
            ],
            [
                'date' => '2026-02-26',
                'change' => '広報室の #意見 / #要望 リンクを絞り込み付きに更新しました。',
                'usage' => '広報室からリンクを開くと関連投稿だけ確認できます。',
            ],
        ];
    }

    /**
     * @return list<array{priority:string,title:string,items:list<string>}>
     */
    private function roadmap(): array
    {
        return [
            [
                'priority' => '優先度A',
                'title' => '広報室と運用導線を整える',
                'items' => [
                    '反映済みの改善と次の予定が混ざらないよう見直します。',
                    '小さな改善を安全に出せる流れを保ちます。',
                ],
            ],
            [
                'priority' => '優先度B',
                'title' => '小さな改善候補を整理する',
                'items' => [
                    '#意見 / #要望 の投稿から、次に扱う改善候補を選びます。',
                    '大きな改修に広げすぎない範囲を決めます。',
                ],
            ],
            [
                'priority' => '優先度C',
                'title' => '保存検索の必要性を見る',
                'items' => [
                    '最近使った検索条件だけで足りるか確認します。',
                    '必要であれば、名前付き保存検索を検討します。',
                ],
            ],
        ];
    }
}
