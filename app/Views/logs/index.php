<?php
declare(strict_types=1);

use App\Support\TextFormatter;
use App\Support\Url;

$likedMap = is_array($likedMap ?? null) ? $likedMap : [];
$redirectTo = (string) ($_SERVER['REQUEST_URI'] ?? Url::to('/logs'));
$query = (string) ($query ?? '');
$fromDate = (string) ($fromDate ?? '');
$toDate = (string) ($toDate ?? '');
$hasSearchCriteria = (bool) ($hasSearchCriteria ?? false);
$resultCount = (int) ($resultCount ?? 0);
$recentSearches = is_array($recentSearches ?? null) ? $recentSearches : [];
$buildLogSearchUrl = static function (array $params): string {
    $clean = [];
    foreach ($params as $key => $value) {
        $value = trim((string) $value);
        if ($value === '') {
            continue;
        }
        $clean[(string) $key] = $value;
    }
    $queryString = http_build_query($clean);
    return Url::to('/logs') . ($queryString !== '' ? '?' . $queryString : '');
};
$describeLogSearch = static function (array $search): string {
    $parts = [];
    $searchQuery = trim((string) ($search['q'] ?? ''));
    $searchFrom = trim((string) ($search['from'] ?? ''));
    $searchTo = trim((string) ($search['to'] ?? ''));
    if ($searchQuery !== '') {
        $parts[] = '検索語「' . $searchQuery . '」';
    }
    if ($searchFrom !== '') {
        $parts[] = '開始日 ' . $searchFrom;
    }
    if ($searchTo !== '') {
        $parts[] = '終了日 ' . $searchTo;
    }
    return implode(' / ', $parts);
};
?>
<h1>過去ログ検索</h1>

<h2>月別ログダウンロード</h2>
<?php if (empty($monthList ?? [])): ?>
    <p>ダウンロードできるログはありません。</p>
<?php else: ?>
    <ul>
        <?php foreach ($monthList as $item): ?>
            <li>
                <?= htmlspecialchars((string) $item['month'], ENT_QUOTES, 'UTF-8') ?>
                (<?= (int) $item['count'] ?>件)
                <a href="<?= Url::to('/logs/download') ?>?month=<?= urlencode((string) $item['month']) ?>">ダウンロード</a>
                <?php
                    $month = (string) $item['month'];
                    $monthStart = $month . '-01';
                    $monthEnd = date('Y-m-t', strtotime($monthStart));
                ?>
                <a href="<?= htmlspecialchars($buildLogSearchUrl(['from' => $monthStart, 'to' => $monthEnd]), ENT_QUOTES, 'UTF-8') ?>">この月で検索</a>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<h2>過去ログ検索</h2>
<form method="get" action="<?= Url::to('/logs') ?>">
    <label for="q">検索語</label>
    <input id="q" name="q" type="text" value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>">

    <label for="from">開始日</label>
    <input id="from" name="from" type="date" value="<?= htmlspecialchars($fromDate, ENT_QUOTES, 'UTF-8') ?>">

    <label for="to">終了日</label>
    <input id="to" name="to" type="date" value="<?= htmlspecialchars($toDate, ENT_QUOTES, 'UTF-8') ?>">

    <button type="submit">検索</button>
</form>

<?php if ($hasSearchCriteria): ?>
    <div class="search-summary">
        <p class="meta">
            検索条件:
            <?php if (trim($query) !== ''): ?>
                <span>検索語「<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>」</span>
            <?php endif; ?>
            <?php if (trim($fromDate) !== ''): ?>
                <span>開始日 <?= htmlspecialchars($fromDate, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
            <?php if (trim($toDate) !== ''): ?>
                <span>終了日 <?= htmlspecialchars($toDate, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </p>
        <p class="meta">検索結果: <?= $resultCount ?>件</p>
        <p class="meta">
            <?php if (trim($query) !== ''): ?>
                <a href="<?= htmlspecialchars($buildLogSearchUrl(['from' => $fromDate, 'to' => $toDate]), ENT_QUOTES, 'UTF-8') ?>">検索語だけ解除</a>
            <?php endif; ?>
            <?php if (trim($fromDate) !== ''): ?>
                <a href="<?= htmlspecialchars($buildLogSearchUrl(['q' => $query, 'to' => $toDate]), ENT_QUOTES, 'UTF-8') ?>">開始日だけ解除</a>
            <?php endif; ?>
            <?php if (trim($toDate) !== ''): ?>
                <a href="<?= htmlspecialchars($buildLogSearchUrl(['q' => $query, 'from' => $fromDate]), ENT_QUOTES, 'UTF-8') ?>">終了日だけ解除</a>
            <?php endif; ?>
            <a href="<?= Url::to('/logs') ?>">条件をすべて解除</a>
        </p>
    </div>
<?php endif; ?>

<?php if ($recentSearches !== []): ?>
    <div class="search-history">
        <h3>最近使った検索条件</h3>
        <ul>
            <?php foreach ($recentSearches as $search): ?>
                <?php if (!is_array($search)) { continue; } ?>
                <?php $description = $describeLogSearch($search); ?>
                <?php if ($description === '') { continue; } ?>
                <li>
                    <a href="<?= htmlspecialchars($buildLogSearchUrl($search), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<h2>検索結果</h2>
<?php if ($hasSearchCriteria && empty($results ?? [])): ?>
    <p>該当する投稿はありません。</p>
<?php endif; ?>
<?php foreach (($results ?? []) as $post): ?>
    <article class="card">
        <div class="post-head">
            <h2 class="post-title"><?= TextFormatter::linkifyHashtags($post->title) ?></h2>
            <span class="post-meta">
                投稿者：<span class="<?= ($post->authorIsGenerated ? 'author-generated' : '') ?>"><?= htmlspecialchars($post->author, ENT_QUOTES, 'UTF-8') ?></span>
                　投稿日時：<?= htmlspecialchars($post->createdAt, ENT_QUOTES, 'UTF-8') ?>
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
