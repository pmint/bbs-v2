<?php
declare(strict_types=1);

use App\Support\Url;

$old = is_array($old ?? null) ? $old : [];
$draftKey = 'bbs-v2:create';
if (!empty($replyToId ?? null)) {
    $draftKey .= ':reply:' . (int) $replyToId;
}
?>
<h1>書き込み</h1>

<?php if (!empty($replyToPost ?? null)): ?>
    <p class="meta">
        返信先：#<?= (int) $replyToPost->id ?>
        （投稿者：<?= htmlspecialchars($replyToPost->author, ENT_QUOTES, 'UTF-8') ?>）
    </p>
<?php endif; ?>

<form method="post" action="<?= Url::to('/posts') ?>" data-draft-form data-draft-key="<?= htmlspecialchars($draftKey, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="_token" value="<?= htmlspecialchars((string) ($csrfToken ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    <?php if (!empty($replyToId ?? null)): ?>
        <input type="hidden" name="reply_to" value="<?= (int) $replyToId ?>">
    <?php endif; ?>

    <div class="form-block">
        <div class="field">
            <label for="author">投稿者</label>
            <input id="author" class="short-input" name="author" type="text" value="<?= htmlspecialchars((string) ($old['author'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <div class="field">
            <label for="title">題名</label>
            <input id="title" class="short-input" name="title" type="text" value="<?= htmlspecialchars((string) ($old['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <?php require __DIR__ . '/_tag_assist.php'; ?>

        <div class="field">
            <label for="body">本文</label>
            <textarea id="body" name="body" rows="8"><?= htmlspecialchars((string) ($old['body'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div class="form-tools">
            <button type="button" data-preview-toggle>確認する</button>
            <button type="button" data-draft-clear>保存分を消す</button>
            <span class="meta" data-draft-status>下書きはこのブラウザに残ります。</span>
        </div>

        <div class="post-preview" data-preview-panel hidden>
            <h2>投稿前確認</h2>
            <div class="post-head">
                <h2 class="post-title" data-preview-title>（題名なし）</h2>
                <span class="post-meta">投稿者：<span data-preview-author>（無記名）</span></span>
            </div>
            <pre data-preview-body>（本文なし）</pre>
        </div>

        <button type="submit">投稿する</button>
    </div>
</form>
