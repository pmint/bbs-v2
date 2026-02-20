<?php
declare(strict_types=1);

use App\Support\TextFormatter;
use App\Support\Url;

$canManageMap = is_array($canManageMap ?? null) ? $canManageMap : [];
$likedMap = is_array($likedMap ?? null) ? $likedMap : [];
$tagList = is_array($tagList ?? null) ? $tagList : [];
$filterQuery = (string) ($filterQuery ?? '');
$ngWordsRaw = (string) ($ngWordsRaw ?? '');
$hasNgWords = (bool) ($hasNgWords ?? false);
$hiddenByNgCount = (int) ($hiddenByNgCount ?? 0);
$isNarrowing = (bool) ($isNarrowing ?? false);
$redirectTo = (string) ($_SERVER['REQUEST_URI'] ?? Url::to('/posts'));
?>
<h1>あやしいわーるど＠あやしいわーるど</h1>
<form method="get" action="<?= Url::to('/posts') ?>">
    <input type="hidden" name="q" value="<?= htmlspecialchars($filterQuery, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="ng" value="<?= htmlspecialchars($ngWordsRaw, ENT_QUOTES, 'UTF-8') ?>">
    <button type="submit">リロード</button>
</form>
<div>
    <button type="button" id="toggle-filter-panel">絞り込みパネルを開く</button>
    <form method="get" action="<?= Url::to('/posts') ?>" style="display:inline-block;">
        <input type="hidden" name="clear_filter" value="1">
        <button type="submit">絞り込み解除</button>
    </form>
</div>
<form method="get" action="<?= Url::to('/posts') ?>">
    <div id="filter-panel" style="display:none;">
        <label for="post-filter-q">絞り込み</label>
        <input id="post-filter-q" name="q" type="text" value="<?= htmlspecialchars($filterQuery, ENT_QUOTES, 'UTF-8') ?>">
        <label for="post-ng-words">NGワード(|区切り)</label>
        <input id="post-ng-words" name="ng" type="text" value="<?= htmlspecialchars($ngWordsRaw, ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit">絞り込み</button>
        <?php if (!empty($tagList)): ?>
            <p class="meta">ハッシュタグ:</p>
            <p class="meta">
                <?php foreach ($tagList as $item): ?>
                    <?php $tag = (string) ($item['tag'] ?? ''); ?>
                    <?php if ($tag === '') { continue; } ?>
                    <a href="<?= Url::to('/posts') ?>?q=<?= urlencode('#' . $tag) ?>&amp;ng=<?= urlencode($ngWordsRaw) ?>">#<?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') ?>(<?= (int) ($item['count'] ?? 0) ?>)</a>
                <?php endforeach; ?>
            </p>
        <?php endif; ?>
    </div>
</form>
<?php if ($hasNgWords && $hiddenByNgCount > 0): ?>
    <p class="meta">NGで非表示：<?= (int) $hiddenByNgCount ?>件</p>
<?php endif; ?>

<script>
(() => {
    const button = document.getElementById('toggle-filter-panel');
    const panel = document.getElementById('filter-panel');
    if (!button || !panel) return;
    button.addEventListener('click', () => {
        const hidden = panel.style.display === 'none';
        panel.style.display = hidden ? 'block' : 'none';
        button.textContent = hidden ? '絞り込みパネルを閉じる' : '絞り込みパネルを開く';
    });
})();
</script>

<?php if (empty($posts)): ?>
    <?php if ($isNarrowing): ?>
        <p>該当する投稿はありません。</p>
    <?php else: ?>
        <p>まだ投稿がありません。</p>
    <?php endif; ?>
<?php else: ?>
    <?php foreach ($posts as $post): ?>
        <article class="card">
            <div class="post-head">
                <h2 class="post-title"><?= htmlspecialchars($post->title, ENT_QUOTES, 'UTF-8') ?></h2>
                <span class="post-meta">
                    投稿者：<?= htmlspecialchars($post->author, ENT_QUOTES, 'UTF-8') ?>
                    　投稿日時：<?= htmlspecialchars($post->createdAt, ENT_QUOTES, 'UTF-8') ?>
                    <span class="post-actions">
                        <a href="<?= Url::to('/posts/create') ?>?reply_to=<?= (int) $post->id ?>" title="返信" target="_blank" rel="noopener noreferrer">■</a>
                        <a href="<?= Url::to('/posts/thread/' . (int) ($post->threadId ?? $post->id)) ?>" title="スレッド表示" target="_blank" rel="noopener noreferrer">◆</a>
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
            <?php if (($canManageMap[(int) $post->id] ?? false) === true): ?>
                <div class="post-controls">
                    <p><a href="<?= Url::to('/posts/' . (int) $post->id . '/edit') ?>" target="_blank" rel="noopener noreferrer">編集</a></p>
                    <form method="post" action="<?= Url::to('/posts/' . (int) $post->id . '/delete') ?>" onsubmit="return confirm('この投稿を削除しますか？');">
                        <input type="hidden" name="_token" value="<?= htmlspecialchars((string) ($csrfToken ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit">削除</button>
                    </form>
                </div>
            <?php endif; ?>
        </article>
        <hr>
    <?php endforeach; ?>
<?php endif; ?>
