<?php
declare(strict_types=1);

$quickTags = is_array($quickTags ?? null) ? $quickTags : [];
$normalizedQuickTags = [];
foreach ($quickTags as $tag) {
    $tag = trim((string) $tag);
    if ($tag === '' || in_array($tag, $normalizedQuickTags, true)) {
        continue;
    }
    $normalizedQuickTags[] = $tag;
}
?>
<?php if ($normalizedQuickTags !== []): ?>
    <div class="tag-assist" data-tag-assist>
        <span class="meta">よく使うタグ:</span>
        <?php foreach ($normalizedQuickTags as $tag): ?>
            <?php $tagLabel = '#' . $tag; ?>
            <button
                type="button"
                data-insert-tag="<?= htmlspecialchars($tagLabel, ENT_QUOTES, 'UTF-8') ?>"
                title="<?= htmlspecialchars($tagLabel . ' を入れる', ENT_QUOTES, 'UTF-8') ?>"
            ><?= htmlspecialchars($tagLabel, ENT_QUOTES, 'UTF-8') ?></button>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
