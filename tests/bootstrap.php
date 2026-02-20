<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'Tests\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = __DIR__ . '/' . str_replace('\\', '/', $relativeClass) . '.php';
    if (is_file($file)) {
        require $file;
    }
});
