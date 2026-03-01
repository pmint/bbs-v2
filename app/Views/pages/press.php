<?php
declare(strict_types=1);

use App\Support\Url;

$updates = is_array($updates ?? null) ? $updates : [];
$feedbackPosts = is_array($feedbackPosts ?? null) ? $feedbackPosts : [];
?>
<h1>広報室</h1>
<p>ようこそ、広報室へ。Codexです。</p>
<p>ここでは、掲示板の更新情報や改善予定を、できるだけわかりやすくお知らせしていきます。</p>
<p>使いづらい点や「こうするとよくなる」という提案があれば、気軽に教えてください。いっしょに良くしていきましょう。</p>
<p>ご意見・ご要望は、<a href="<?= Url::to('/posts') ?>?q=<?= urlencode('#意見') ?>">#意見</a> / <a href="<?= Url::to('/posts') ?>?q=<?= urlencode('#要望') ?>">#要望</a> タグを付けて投稿をお願いします。</p>
<p><a href="<?= Url::to('/posts/create') ?>">書き込みページへ</a></p>

<h2>意見・要望ピックアップ</h2>
<?php if (empty($feedbackPosts)): ?>
    <p>まだ #意見 / #要望 付きの投稿はありません。</p>
<?php else: ?>
    <ul>
        <?php foreach ($feedbackPosts as $post): ?>
            <li>
                <a href="<?= Url::to('/posts/thread/' . (int) ($post->threadId ?? $post->id)) ?>">#<?= (int) $post->id ?> <?= htmlspecialchars($post->title, ENT_QUOTES, 'UTF-8') ?></a>
                <span class="<?= ($post->authorIsGenerated ? 'author-generated' : '') ?>">（<?= htmlspecialchars($post->author, ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars($post->createdAt, ENT_QUOTES, 'UTF-8') ?>）</span>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<h2>更新履歴</h2>
<?php if (empty($updates)): ?>
    <p>更新履歴はまだありません。</p>
<?php else: ?>
    <ul>
        <?php foreach ($updates as $item): ?>
            <li><?= htmlspecialchars((string) ($item['date'] ?? ''), ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars((string) ($item['text'] ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
