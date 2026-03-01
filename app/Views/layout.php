<?php
declare(strict_types=1);

use App\Support\Url;

$flashMessages = is_array($flashMessages ?? null) ? $flashMessages : [];
$basePath = Url::basePath();
$manifestUrl = ($basePath !== '' ? $basePath : '') . '/manifest.webmanifest';
$swUrl = ($basePath !== '' ? $basePath : '') . '/sw.js';
$siteTitle = 'あやしいわーるど＠あやしいわーるど';
$pageTitle = trim((string) ($title ?? ''));
$documentTitle = $pageTitle !== '' ? $pageTitle . ' | ' . $siteTitle : $siteTitle;
?>
<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#004040">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="manifest" href="<?= htmlspecialchars($manifestUrl, ENT_QUOTES, 'UTF-8') ?>">
    <title><?= htmlspecialchars($documentTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        html { scroll-behavior: smooth; }
        body {
            margin: 16px;
            background: #004040;
            color: #efefef;
            font-family: "Yu Gothic UI", "Meiryo", "MS PGothic", sans-serif;
            font-size: 14px;
            line-height: 1.6;
            -webkit-text-size-adjust: 100%;
        }
        a:link { color: #ccffee; }
        a:visited { color: #dddddd; }
        a:active { color: #ff0000; }
        a:hover { color: #11eeee; text-decoration: underline; }
        hr {
            border: 0 none;
            height: 2px;
            background-color: #c0c0c0;
            color: #c0c0c0;
            margin: 10px 0;
        }
        .container {
            max-width: 980px;
            margin: 0 auto;
            padding: 0;
        }
        .nav { font-size: 0.95rem; margin-bottom: 8px; }
        .nav a { margin-right: 14px; text-decoration: none; }
        .footer {
            margin-top: 14px;
            padding-top: 10px;
            font-size: 0.9rem;
            text-align: right;
        }
        h1 {
            margin: 2px 0 10px;
            font-size: 1rem;
            font-weight: bold;
            color: #fffffe;
        }
        h2 {
            margin: 0;
            font-size: 1rem;
            font-weight: bold;
            color: #fffffe;
        }
        .post-title {
            display: inline;
            font-size: 0.92rem;
            font-weight: normal;
            color: #b7caca;
        }
        .post-title a:link,
        .post-title a:visited {
            color: #b7caca;
        }
        .post-head {
            margin: 0 0 8px;
            white-space: nowrap;
            overflow-x: auto;
        }
        .post-head .post-meta {
            font-size: 0.92rem;
            color: #b7caca;
            margin-left: 0.8em;
        }
        .author-generated {
            color: #9cb8b8;
        }
        .post-head .post-actions { margin-left: 0.7em; }
        .post-head .post-actions a { text-decoration: none; margin-right: 0.4em; }
        .card {
            padding: 8px 4px 12px;
            margin: 0;
        }
        .meta { font-size: 0.92rem; color: #efefef; }
        .error { color: #ff6666; margin: 8px 0; font-weight: bold; }
        .success { color: #9ff5c4; margin: 8px 0; font-weight: bold; }
        .form-block { max-width: 760px; }
        .field { margin: 0 0 14px; }
        .field label {
            display: block;
            margin-bottom: 5px;
            font-weight: normal;
        }
        input[type="text"], textarea {
            width: 100%;
            box-sizing: border-box;
            max-width: 100%;
            background-color: #eeeeee;
            border: 2px solid #999999;
            border-radius: 2px;
            margin: 0;
            padding: 5px 6px;
            color: #111111;
        }
        .short-input { max-width: 28rem; }
        input[type="text"]:hover, textarea:hover {
            background-color: #ccddff;
            border-color: #888888;
        }
        input[type="text"]:focus, textarea:focus {
            background-color: #cccccc;
            border-color: #0088cc;
            outline: none;
        }
        button {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            border: 2px solid #999999;
            border-radius: 2px;
            background: #d2d2d2;
            padding: 1px 0.5em;
            margin-right: 0.5em;
            color: #111111;
            cursor: pointer;
        }
        button:active { background-color: #aabbdd; border-color: #0088cc; }
        .post-controls { margin-top: 6px; }
        .post-controls p { margin: 0; display: inline-block; margin-right: 10px; }
        .post-controls form { margin: 0; display: inline-block; }
        .list-toolbar {
            display: flex;
            align-items: center;
            gap: 1.4rem;
            margin: 0 0 12px;
            flex-wrap: wrap;
        }
        .list-toolbar form { margin: 0; }
        .unread-reply-bar {
            margin: 0 0 10px;
            font-size: 0.92rem;
            color: #e7f8f4;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem 0.8rem;
            align-items: center;
        }
        .unread-reply-bar strong { color: #ffffff; }
        .unread-reply-bar a { white-space: nowrap; }
        .filter-panel {
            margin: 0 0 14px;
            max-width: 760px;
        }
        .tag-controls {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 0 0 10px;
            flex-wrap: wrap;
        }
        .tag-controls .meta { margin: 0; }
        .tag-controls .tag-list {
            flex: 1 1 auto;
            min-width: 0;
            white-space: nowrap;
            overflow-x: auto;
            overflow-y: hidden;
            padding-bottom: 2px;
            scrollbar-width: thin;
            scrollbar-color: rgba(239, 239, 239, 0.35) transparent;
        }
        .tag-controls .tag-list::-webkit-scrollbar {
            height: 6px;
        }
        .tag-controls .tag-list::-webkit-scrollbar-track {
            background: transparent;
        }
        .tag-controls .tag-list::-webkit-scrollbar-thumb {
            background: rgba(239, 239, 239, 0.3);
            border-radius: 8px;
        }
        .tag-controls .tag-list::-webkit-scrollbar-thumb:hover {
            background: rgba(239, 239, 239, 0.45);
        }
        .tag-controls .clear-filter-link { white-space: nowrap; }
        .filter-actions {
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 0.9rem;
            flex-wrap: wrap;
        }
        .like-btn {
            margin-top: 10px;
            background: transparent;
            color: #ffffff;
            border: none;
            padding: 0;
        }
        .like-btn .like-icon {
            font-size: 1.1em;
            line-height: 1;
            display: inline-flex;
            width: 1.2em;
            justify-content: center;
            align-items: center;
            vertical-align: baseline;
        }
        .toast-container {
            position: fixed;
            top: 12px;
            right: 12px;
            z-index: 9999;
            width: min(92vw, 420px);
        }
        .toast {
            border: 2px solid #999999;
            border-radius: 2px;
            background: #d2d2d2;
            color: #111111;
            padding: 7px 10px;
            margin-bottom: 8px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            opacity: 1;
            transition: opacity 0.2s ease;
        }
        .toast.is-hiding { opacity: 0; }
        .toast-success { border-color: #5f9f7f; background: #d7f1e1; }
        .toast-error { border-color: #aa6666; background: #f3d8d8; }
        .toast-info { border-color: #6a7ea6; background: #dce6f8; }
        .toast-message { margin: 0; line-height: 1.4; }
        .toast-close {
            margin: 0;
            border: 0;
            background: transparent;
            color: #333333;
            cursor: pointer;
            font-weight: bold;
            padding: 0 2px;
            line-height: 1;
        }
        pre {
            margin: 0;
            line-height: 1.75;
            white-space: pre-wrap;
            word-wrap: break-word;
            font-size: 0.95rem;
        }
        @media (max-width: 640px) {
            body { margin: 10px; }
            .container { padding: 8px 10px 10px; }
            .post-head { white-space: normal; overflow-x: visible; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="nav">
        <a href="<?= Url::to('/posts') ?>">掲示板</a>
        <a href="<?= Url::to('/posts/create') ?>">書き込み</a>
        <a href="<?= Url::to('/logs') ?>">過去ログ</a>
        <a href="<?= Url::to('/press') ?>">広報室</a>
        <a href="mailto:pmint.name@gmail.com">連絡先✉️</a>
    </div>
    <hr>
    <?= $content ?>
    <div class="footer">
        <a href="https://github.com/pmint/bbs-v2">bbs-v2</a>
    </div>
</div>
<div id="toast-container" class="toast-container" aria-live="polite" aria-atomic="false"></div>
<script>
(() => {
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('<?= htmlspecialchars($swUrl, ENT_QUOTES, 'UTF-8') ?>').catch(() => {});
        });
    }

    const messages = <?= json_encode($flashMessages, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    if (!Array.isArray(messages) || messages.length === 0) return;

    const container = document.getElementById('toast-container');
    if (!container) return;

    const removeToast = (toast) => {
        if (!toast) return;
        toast.classList.add('is-hiding');
        window.setTimeout(() => toast.remove(), 220);
    };

    messages.forEach((entry) => {
        if (!entry || typeof entry.text !== 'string' || entry.text.trim() === '') return;
        let type = 'info';
        if (entry.type === 'success') type = 'success';
        else if (entry.type === 'error') type = 'error';
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;

        const text = document.createElement('p');
        text.className = 'toast-message';
        text.textContent = entry.text;

        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'toast-close';
        close.setAttribute('aria-label', '閉じる');
        close.textContent = '×';
        close.addEventListener('click', () => removeToast(toast));

        toast.appendChild(text);
        toast.appendChild(close);
        container.appendChild(toast);

        window.setTimeout(() => removeToast(toast), 4000);
    });
})();
</script>
</body>
</html>
