<?php
declare(strict_types=1);

use App\Support\TextFormatter;
use App\Support\Url;

$likedMap = is_array($likedMap ?? null) ? $likedMap : [];
$redirectTo = (string) ($_SERVER['REQUEST_URI'] ?? Url::to('/posts'));
?>
<h1>スレッド表示（#<?= (int) ($threadId ?? 0) ?>）</h1>
<p><a href="<?= Url::to('/posts') ?>">掲示板に戻る</a></p>

<?php if (empty($posts ?? [])): ?>
    <p>投稿がありません。</p>
<?php else: ?>
    <?php foreach ($posts as $post): ?>
        <article class="card">
            <div class="post-head">
                <h2 class="post-title"><?= htmlspecialchars($post->title, ENT_QUOTES, 'UTF-8') ?></h2>
                <span class="post-meta">
                    投稿者：<span class="<?= ($post->authorIsGenerated ? 'author-generated' : '') ?>"><?= htmlspecialchars($post->author, ENT_QUOTES, 'UTF-8') ?></span>
                    　投稿日時：<?= htmlspecialchars($post->createdAt, ENT_QUOTES, 'UTF-8') ?>
                    <?php if ($post->parentId !== null): ?>
                        　返信先：#<?= (int) $post->parentId ?>
                    <?php endif; ?>
                    <span class="post-actions">
                        <a href="<?= Url::to('/posts/create') ?>?reply_to=<?= (int) $post->id ?>" title="返信">■</a>
                        <a href="<?= Url::to('/posts/thread/' . (int) ($post->threadId ?? $post->id)) ?>" title="スレッド表示">◆</a>
                    </span>
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
