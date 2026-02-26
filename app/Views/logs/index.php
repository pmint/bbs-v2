<?php
declare(strict_types=1);

use App\Support\TextFormatter;
use App\Support\Url;

$likedMap = is_array($likedMap ?? null) ? $likedMap : [];
$redirectTo = (string) ($_SERVER['REQUEST_URI'] ?? Url::to('/logs'));
?>
<h1>過去ログ検索</h1>

<h2>日付別ログダウンロード</h2>
<?php if (empty($dateList ?? [])): ?>
    <p>ダウンロードできるログはありません。</p>
<?php else: ?>
    <ul>
        <?php foreach ($dateList as $item): ?>
            <li>
                <?= htmlspecialchars((string) $item['date'], ENT_QUOTES, 'UTF-8') ?>
                (<?= (int) $item['count'] ?>件)
                <a href="<?= Url::to('/logs/download') ?>?date=<?= urlencode((string) $item['date']) ?>">ダウンロード</a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<h2>過去ログ検索</h2>
<form method="get" action="<?= Url::to('/logs') ?>">
    <label for="q">検索語</label>
    <input id="q" name="q" type="text" value="<?= htmlspecialchars((string) ($query ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <label for="from">開始日</label>
    <input id="from" name="from" type="date" value="<?= htmlspecialchars((string) ($fromDate ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <label for="to">終了日</label>
    <input id="to" name="to" type="date" value="<?= htmlspecialchars((string) ($toDate ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <button type="submit">検索</button>
</form>

<h2>検索結果</h2>
<?php if (empty($results ?? [])): ?>
    <p>該当する投稿はありません。</p>
<?php else: ?>
    <?php foreach ($results as $post): ?>
        <article class="card">
            <div class="post-head">
                <h2 class="post-title"><?= htmlspecialchars($post->title, ENT_QUOTES, 'UTF-8') ?></h2>
                <span class="post-meta">
                    投稿者：<span class="<?= ($post->authorIsGenerated ? 'author-generated' : '') ?>"><?= htmlspecialchars($post->author, ENT_QUOTES, 'UTF-8') ?></span>
                    　投稿日時：<?= htmlspecialchars($post->createdAt, ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
            <pre><?= TextFormatter::linkifyUrls($post->body) ?></pre>
            <form method="post" action="<?= Url::to('/posts/' . (int) $post->id . '/like') ?>">
                <input type="hidden" name="_token" value="<?= htmlspecialchars((string) ($csrfToken ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="redirect_to" value="<?= htmlspecialchars($redirectTo, ENT_QUOTES, 'UTF-8') ?>">
                <?php $isLiked = (bool) ($likedMap[(int) $post->id] ?? false); ?>
                <button type="submit" class="like-btn<?= ($isLiked ? ' is-liked' : '') ?>"><span class="like-icon"><?= ($isLiked ? '♥' : '♡') ?></span> <?= (int) ($post->likeCount ?? 0) ?></button>
            </form>
        </article>
        <hr>
    <?php endforeach; ?>
<?php endif; ?>
