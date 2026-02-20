<?php

declare(strict_types=1);

namespace App\Support;

final class Url
{
    private static ?array $config = null;

    public static function basePath(): string
    {
        $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        $base = str_replace('\\', '/', dirname($scriptName));
        if ($base === '/' || $base === '.' || $base === '') {
            return '';
        }
        return rtrim($base, '/');
    }

    public static function to(string $path = '/'): string
    {
        $base = self::basePath();
        $mode = (string) (self::config()['url_mode'] ?? 'rewrite');
        $useIndexPhp = $mode === 'index_php';
        $path = trim($path);
        if ($path === '' || $path === '/') {
            if ($useIndexPhp) {
                return ($base !== '' ? $base : '') . '/index.php';
            }
            return $base !== '' ? $base . '/' : '/';
        }

        if ($path[0] !== '/') {
            $path = '/' . $path;
        }

        if ($useIndexPhp) {
            return ($base !== '' ? $base : '') . '/index.php' . $path;
        }

        return $base . $path;
    }

    /** @return array<string,mixed> */
    private static function config(): array
    {
        if (self::$config !== null) {
            return self::$config;
        }

        $configPath = __DIR__ . '/../../config/app.php';
        if (!is_file($configPath)) {
            self::$config = [];
            return self::$config;
        }

        $loaded = require $configPath;
        self::$config = is_array($loaded) ? $loaded : [];
        return self::$config;
    }
}
