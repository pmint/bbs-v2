<?php

declare(strict_types=1);

$configPath = __DIR__ . '/../config/app.php';
$config = is_file($configPath) ? (require $configPath) : [];

$timezone = (string) ($config['timezone'] ?? 'UTC');
date_default_timezone_set($timezone);

$debug = (bool) ($config['debug'] ?? false);
if ($debug) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = __DIR__ . '/' . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

session_start();
