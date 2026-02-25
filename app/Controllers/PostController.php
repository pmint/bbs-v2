<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\PostService;
use App\Support\Csrf;
use App\Support\Hashtag;
use App\Support\Url;
use App\Support\View;
use App\Models\Post;
use InvalidArgumentException;
use RuntimeException;

final class PostController
{
    public function __construct(private PostService $service)
    {
    }

    public function index(): void
    {
        $ownerKey = $this->getOrCreateOwnerKey();
        $ownerKeyHash = hash('sha256', $ownerKey);
        $allPosts = $this->service->listPosts();
        $markReadReplyId = $this->resolveMarkReadReplyId();
        if ($markReadReplyId !== null) {
            $this->markReplyAsRead($allPosts, $ownerKeyHash, $markReadReplyId);
        }
        $unreadReplyItems = $this->buildUnreadReplyItems($allPosts, $ownerKeyHash);
        $replyNotices = $this->buildReplyNotices($allPosts, $ownerKeyHash);
        $filterQuery = $this->resolvePostFilterQuery();
        $ngWordsRaw = $this->resolvePostNgWordsRaw();
        $ngWords = $this->parseNgWords($ngWordsRaw);
        $hasNgWords = $ngWords !== [];
        $isFiltering = trim($filterQuery) !== '';
        $isNarrowing = $isFiltering || $hasNgWords;
        $posts = $this->service->listPostsByQuery($filterQuery);
        $hiddenByNgCount = 0;
        if ($hasNgWords) {
            [$posts, $hiddenByNgCount] = $this->applyNgWordFilter($posts, $ngWords);
        }
        $recentPostsForTags = $this->service->listRecentPosts(7);
        if ($hasNgWords) {
            [$recentPostsForTags, ] = $this->applyNgWordFilter($recentPostsForTags, $ngWords);
        }
        $tagList = Hashtag::buildTagCounts($recentPostsForTags, 15);
        $canManageMap = [];
        $likedMap = $this->buildLikedMap($posts);
        foreach ($posts as $post) {
            if ($post->id === null) {
                continue;
            }
            $canManageMap[(int) $post->id] = $this->service->canModifyPost((int) $post->id, $ownerKey);
        }

        View::render('posts/index', [
            'title' => '投稿一覧',
            'posts' => $posts,
            'filterQuery' => $filterQuery,
            'ngWordsRaw' => $ngWordsRaw,
            'hasNgWords' => $hasNgWords,
            'hiddenByNgCount' => $hiddenByNgCount,
            'isNarrowing' => $isNarrowing,
            'tagList' => $tagList,
            'notices' => $replyNotices,
            'unreadReplyItems' => $unreadReplyItems,
            'unreadReplyCount' => count($unreadReplyItems),
            'canManageMap' => $canManageMap,
            'likedMap' => $likedMap,
            'old' => $_SESSION['old'] ?? [],
            'csrfToken' => Csrf::token(),
        ]);
        unset($_SESSION['old']);
    }

    public function create(): void
    {
        $replyToId = (int) ($_GET['reply_to'] ?? 0);
        $replyToPost = null;
        $old = is_array($_SESSION['old'] ?? null) ? $_SESSION['old'] : [];
        if ($replyToId > 0) {
            try {
                $replyToPost = $this->service->getPost($replyToId);
                if (!isset($old['title']) || trim((string) $old['title']) === '') {
                    $old['title'] = '＞' . $replyToPost->author;
                }
                if (!isset($old['body']) || trim((string) $old['body']) === '') {
                    $old['body'] = $this->buildQuotedBody($replyToPost->body) . PHP_EOL . PHP_EOL;
                }
            } catch (RuntimeException $e) {
                $_SESSION['errors'] = [$e->getMessage()];
                header('Location: ' . Url::to('/posts'));
                exit;
            }
        }

        View::render('posts/create', [
            'title' => '新規投稿',
            'old' => $old,
            'csrfToken' => Csrf::token(),
            'replyToPost' => $replyToPost,
            'replyToId' => $replyToId > 0 ? $replyToId : null,
        ]);

        unset($_SESSION['old']);
    }

    public function store(): void
    {
        if (!$this->verifyCsrf()) {
            $this->redirectWithErrors('/posts/create', ['不正なリクエストです。']);
        }

        $author = (string) ($_POST['author'] ?? '');
        $title = (string) ($_POST['title'] ?? '');
        $body = (string) ($_POST['body'] ?? '');
        $replyToId = (int) ($_POST['reply_to'] ?? 0);
        $replyToId = $replyToId > 0 ? $replyToId : null;

        try {
            $this->service->createPostWithOwnerKey($author, $title, $body, $this->getOrCreateOwnerKey(), $replyToId);
            $_SESSION['success'] = '投稿を作成しました。';
            header('Location: ' . Url::to('/posts'));
            exit;
        } catch (InvalidArgumentException $e) {
            $path = '/posts/create' . ($replyToId !== null ? '?reply_to=' . $replyToId : '');
            $this->redirectWithErrors($path, [$e->getMessage()], [
                'author' => $author,
                'title' => $title,
                'body' => $body,
            ]);
        } catch (RuntimeException $e) {
            $path = '/posts/create' . ($replyToId !== null ? '?reply_to=' . $replyToId : '');
            $this->redirectWithErrors($path, [$e->getMessage()], [
                'author' => $author,
                'title' => $title,
                'body' => $body,
            ]);
        }
    }

    /** @param array<string,string> $params */
    public function edit(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0) {
            $this->redirectWithErrors('/posts', ['不正な投稿IDです。']);
        }

        try {
            if (!$this->service->canModifyPost($id, $this->getOrCreateOwnerKey())) {
                $this->redirectWithErrors('/posts', ['この投稿は現在のセッションでは編集できません。']);
            }
            $post = $this->service->getPost($id);
            View::render('posts/edit', [
                'title' => '投稿編集',
                'post' => $post,
                'old' => $_SESSION['old'] ?? [],
                'csrfToken' => Csrf::token(),
            ]);
            unset($_SESSION['old']);
        } catch (RuntimeException $e) {
            $this->redirectWithErrors('/posts', [$e->getMessage()]);
        }
    }

    /** @param array<string,string> $params */
    public function update(array $params): void
    {
        if (!$this->verifyCsrf()) {
            $this->redirectWithErrors('/posts', ['不正なリクエストです。']);
        }

        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0) {
            $this->redirectWithErrors('/posts', ['不正な投稿IDです。']);
        }

        $author = (string) ($_POST['author'] ?? '');
        $title = (string) ($_POST['title'] ?? '');
        $body = (string) ($_POST['body'] ?? '');

        try {
            $this->service->updatePost($id, $author, $title, $body, $this->getOrCreateOwnerKey());
            $_SESSION['success'] = '投稿を更新しました。';
            header('Location: ' . Url::to('/posts'));
            exit;
        } catch (InvalidArgumentException $e) {
            $this->redirectWithErrors('/posts/' . $id . '/edit', [$e->getMessage()], [
                'author' => $author,
                'title' => $title,
                'body' => $body,
            ]);
        } catch (RuntimeException $e) {
            $this->redirectWithErrors('/posts', [$e->getMessage()]);
        }
    }

    /** @param array<string,string> $params */
    public function destroy(array $params): void
    {
        if (!$this->verifyCsrf()) {
            $this->redirectWithErrors('/posts', ['不正なリクエストです。']);
        }

        $id = (int) ($params['id'] ?? 0);
        if ($id <= 0) {
            $this->redirectWithErrors('/posts', ['不正な投稿IDです。']);
        }

        try {
            $this->service->deletePost($id, $this->getOrCreateOwnerKey());
            $_SESSION['success'] = '投稿を削除しました。';
            header('Location: ' . Url::to('/posts'));
            exit;
        } catch (RuntimeException $e) {
            $this->redirectWithErrors('/posts', [$e->getMessage()]);
        }
    }

    /** @param array<string,string> $params */
    public function thread(array $params): void
    {
        $threadId = (int) ($params['id'] ?? 0);
        if ($threadId <= 0) {
            $this->redirectWithErrors('/posts', ['不正なスレッドIDです。']);
        }

        try {
            $posts = $this->service->listThreadPosts($threadId);
            View::render('posts/thread', [
                'title' => 'スレッド表示',
                'threadId' => $threadId,
                'posts' => $posts,
                'likedMap' => $this->buildLikedMap($posts),
                'csrfToken' => Csrf::token(),
            ]);
        } catch (RuntimeException $e) {
            $this->redirectWithErrors('/posts', [$e->getMessage()]);
        }
    }

    /** @param array<string,string> $params */
    public function toggleLike(array $params): void
    {
        if (!$this->verifyCsrf()) {
            $this->redirectWithErrors('/posts', ['不正なリクエストです。']);
        }

        $id = (int) ($params['id'] ?? 0);
        $redirectTo = (string) ($_POST['redirect_to'] ?? '');
        $redirectPath = $this->sanitizeRedirectPath($redirectTo);

        if ($id <= 0) {
            $this->redirectWithErrors($redirectPath, ['不正な投稿IDです。']);
        }

        $likedIds = $this->getLikedPostIdsMap();
        $isLiked = isset($likedIds[$id]);

        try {
            $this->service->toggleLike($id, $isLiked);
            if ($isLiked) {
                unset($likedIds[$id]);
            } else {
                $likedIds[$id] = true;
            }
            $this->saveLikedPostIdsMap($likedIds);
            header('Location: ' . $redirectPath);
            exit;
        } catch (RuntimeException $e) {
            $this->redirectWithErrors($redirectPath, [$e->getMessage()]);
        }
    }

    private function verifyCsrf(): bool
    {
        $token = (string) ($_POST['_token'] ?? '');
        return Csrf::verify($token);
    }

    private function buildQuotedBody(string $body): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $body);
        $lines = explode("\n", $normalized);
        $quoted = array_map(
            static fn(string $line): string => '> ' . $line,
            $lines
        );
        return implode(PHP_EOL, $quoted);
    }

    private function getOrCreateOwnerKey(): string
    {
        $sessionKey = $_SESSION['owner_key'] ?? null;
        if (is_string($sessionKey) && $sessionKey !== '') {
            return $sessionKey;
        }
        $sessionKey = bin2hex(random_bytes(32));
        $_SESSION['owner_key'] = $sessionKey;
        return $sessionKey;
    }

    /**
     * @param list<Post> $posts
     * @return array<int,bool>
     */
    private function buildLikedMap(array $posts): array
    {
        $likedIds = $this->getLikedPostIdsMap();
        $likedMap = [];
        foreach ($posts as $post) {
            if ($post->id === null) {
                continue;
            }
            $id = (int) $post->id;
            $likedMap[$id] = isset($likedIds[$id]);
        }
        return $likedMap;
    }

    /** @return array<int,bool> */
    private function getLikedPostIdsMap(): array
    {
        $raw = $_SESSION['liked_post_ids'] ?? [];
        $map = [];
        if (!is_array($raw)) {
            return $map;
        }
        foreach ($raw as $id) {
            $id = (int) $id;
            if ($id <= 0) {
                continue;
            }
            $map[$id] = true;
        }
        return $map;
    }

    /** @param array<int,bool> $map */
    private function saveLikedPostIdsMap(array $map): void
    {
        $ids = array_keys($map);
        rsort($ids, SORT_NUMERIC);
        $_SESSION['liked_post_ids'] = array_slice($ids, 0, 500);
    }

    private function sanitizeRedirectPath(string $redirectTo): string
    {
        if ($redirectTo !== '' && str_starts_with($redirectTo, '/')) {
            return $redirectTo;
        }
        return Url::to('/posts');
    }

    private function resolveMarkReadReplyId(): ?int
    {
        if (!array_key_exists('mark_read_reply_id', $_GET)) {
            return null;
        }
        $replyId = (int) ($_GET['mark_read_reply_id'] ?? 0);
        return $replyId > 0 ? $replyId : null;
    }

    /** @param list<Post> $allPosts */
    private function markReplyAsRead(array $allPosts, string $ownerKeyHash, int $replyId): void
    {
        $myPostIds = $this->buildMyPostIds($allPosts, $ownerKeyHash);
        foreach ($allPosts as $post) {
            if ($post->id === null || (int) $post->id !== $replyId) {
                continue;
            }
            if ($post->parentId === null) {
                return;
            }
            $parentId = (int) $post->parentId;
            if (!isset($myPostIds[$parentId])) {
                return;
            }
            if ($post->ownerKeyHash === $ownerKeyHash) {
                return;
            }
            $readIds = $this->getReadReplyIdsMap();
            $readIds[$replyId] = true;
            $this->saveReadReplyIdsMap($readIds);
            return;
        }
    }

    /**
     * @param list<Post> $allPosts
     * @return list<array{replyId:int,parentId:int,parentTitle:string,replyAuthor:string,replyCreatedAt:string}>
     */
    private function buildUnreadReplyItems(array $allPosts, string $ownerKeyHash): array
    {
        $myPostIds = $this->buildMyPostIds($allPosts, $ownerKeyHash);
        $titlesById = [];
        foreach ($allPosts as $post) {
            if ($post->id === null) {
                continue;
            }
            $titlesById[(int) $post->id] = $post->title;
        }

        $readIds = $this->getReadReplyIdsMap();
        $items = [];
        foreach ($allPosts as $post) {
            if ($post->id === null || $post->parentId === null) {
                continue;
            }
            $replyId = (int) $post->id;
            $parentId = (int) $post->parentId;
            if (!isset($myPostIds[$parentId])) {
                continue;
            }
            if ($post->ownerKeyHash === $ownerKeyHash) {
                continue;
            }
            if (isset($readIds[$replyId])) {
                continue;
            }

            $items[] = [
                'replyId' => $replyId,
                'parentId' => $parentId,
                'parentTitle' => (string) ($titlesById[$parentId] ?? '（題名なし）'),
                'replyAuthor' => $post->author,
                'replyCreatedAt' => $post->createdAt,
            ];
        }
        return $items;
    }

    /**
     * @param list<Post> $allPosts
     * @return array<int,bool>
     */
    private function buildMyPostIds(array $allPosts, string $ownerKeyHash): array
    {
        $myPostIds = [];
        foreach ($allPosts as $post) {
            if ($post->id === null) {
                continue;
            }
            if ($post->ownerKeyHash === $ownerKeyHash) {
                $myPostIds[(int) $post->id] = true;
            }
        }
        return $myPostIds;
    }

    /** @return array<int,bool> */
    private function getReadReplyIdsMap(): array
    {
        $raw = $_SESSION['read_reply_ids'] ?? [];
        $map = [];
        if (!is_array($raw)) {
            return $map;
        }
        foreach ($raw as $id) {
            $id = (int) $id;
            if ($id <= 0) {
                continue;
            }
            $map[$id] = true;
        }
        return $map;
    }

    /** @param array<int,bool> $map */
    private function saveReadReplyIdsMap(array $map): void
    {
        $ids = array_keys($map);
        rsort($ids, SORT_NUMERIC);
        $_SESSION['read_reply_ids'] = array_slice($ids, 0, 500);
    }

    /**
     * @param list<Post> $allPosts
     * @return list<string>
     */
    private function buildReplyNotices(array $allPosts, string $ownerKeyHash): array
    {
        $myPostIds = [];
        $titlesById = [];
        foreach ($allPosts as $post) {
            if ($post->id === null) {
                continue;
            }
            $titlesById[(int) $post->id] = $post->title;
            if ($post->ownerKeyHash === $ownerKeyHash) {
                $myPostIds[(int) $post->id] = true;
            }
        }

        $notifiedIdsRaw = $_SESSION['notified_reply_ids'] ?? [];
        $notifiedIds = [];
        if (is_array($notifiedIdsRaw)) {
            foreach ($notifiedIdsRaw as $id) {
                $id = (int) $id;
                if ($id > 0) {
                    $notifiedIds[$id] = true;
                }
            }
        }

        $notices = [];
        foreach ($allPosts as $post) {
            if ($post->id === null || $post->parentId === null) {
                continue;
            }
            $replyId = (int) $post->id;
            $parentId = (int) $post->parentId;
            if (!isset($myPostIds[$parentId])) {
                continue;
            }
            if (isset($notifiedIds[$replyId])) {
                continue;
            }
            if ($post->ownerKeyHash === $ownerKeyHash) {
                $notifiedIds[$replyId] = true;
                continue;
            }

            $parentTitle = (string) ($titlesById[$parentId] ?? '（題名なし）');
            $notices[] = 'あなたの投稿「' . $parentTitle . '」に返信がありました。';
            $notifiedIds[$replyId] = true;
        }

        $stored = array_keys($notifiedIds);
        rsort($stored, SORT_NUMERIC);
        $_SESSION['notified_reply_ids'] = array_slice($stored, 0, 500);

        return $notices;
    }

    private function resolvePostFilterQuery(): string
    {
        if (isset($_GET['clear_filter'])) {
            $this->clearPostFilterQuery();
            return '';
        }

        if (array_key_exists('q', $_GET)) {
            $query = trim((string) ($_GET['q'] ?? ''));
            if ($query === '') {
                $this->clearPostFilterQuery();
                return '';
            }
            $this->savePostFilterQuery($query);
            return $query;
        }

        $saved = $_SESSION['post_filter_query'] ?? '';
        return is_string($saved) ? $saved : '';
    }

    private function savePostFilterQuery(string $query): void
    {
        $_SESSION['post_filter_query'] = $query;
    }

    private function clearPostFilterQuery(): void
    {
        unset($_SESSION['post_filter_query']);
    }

    private function resolvePostNgWordsRaw(): string
    {
        if (isset($_GET['clear_ng'])) {
            $this->clearPostNgWordsRaw();
            return '';
        }

        if (array_key_exists('ng', $_GET)) {
            $raw = trim((string) ($_GET['ng'] ?? ''));
            if ($raw === '') {
                $this->clearPostNgWordsRaw();
                return '';
            }
            $this->savePostNgWordsRaw($raw);
            return $raw;
        }

        $saved = $_SESSION['post_ng_words_raw'] ?? '';
        return is_string($saved) ? $saved : '';
    }

    private function savePostNgWordsRaw(string $raw): void
    {
        $_SESSION['post_ng_words_raw'] = $raw;
    }

    private function clearPostNgWordsRaw(): void
    {
        unset($_SESSION['post_ng_words_raw']);
    }

    /** @return list<string> */
    private function parseNgWords(string $raw): array
    {
        $parts = explode('|', $raw);
        $words = [];
        foreach ($parts as $part) {
            $word = trim($part);
            if ($word === '') {
                continue;
            }
            $words[] = $word;
        }
        return array_values(array_unique($words));
    }

    /**
     * @param list<Post> $posts
     * @param list<string> $ngWords
     * @return array{0:list<Post>,1:int}
     */
    private function applyNgWordFilter(array $posts, array $ngWords): array
    {
        $visible = [];
        $hidden = 0;
        foreach ($posts as $post) {
            if ($this->matchesAnyNgWord($post, $ngWords)) {
                $hidden++;
                continue;
            }
            $visible[] = $post;
        }
        return [$visible, $hidden];
    }

    /** @param list<string> $ngWords */
    private function matchesAnyNgWord(Post $post, array $ngWords): bool
    {
        $haystack = $post->author . ' ' . $post->title . ' ' . $post->body;
        foreach ($ngWords as $word) {
            if (mb_stripos($haystack, $word) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param list<string> $errors
     * @param array<string,string> $old
     */
    private function redirectWithErrors(string $path, array $errors, array $old = []): void
    {
        $_SESSION['errors'] = $errors;
        if ($old !== []) {
            $_SESSION['old'] = $old;
        }
        header('Location: ' . Url::to($path));
        exit;
    }
}
