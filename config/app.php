<?php

declare(strict_types=1);

$config = [
    'app_name' => 'bbs-v2',
    'app_url' => 'https://ayashii.world/cgi-bin/bbs-v2/public/',
    'url_mode' => 'index_php',
    'debug' => false,
    'timezone' => 'Asia/Tokyo',
    'database' => [
        'path' => __DIR__ . '/../storage/data/bbs.sqlite',
    ],
];

$localPath = __DIR__ . '/app.local.php';
if (is_file($localPath)) {
    $local = require $localPath;
    if (is_array($local)) {
        $config = array_replace_recursive($config, $local);
    }
}

return $config;
