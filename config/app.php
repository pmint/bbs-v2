<?php

declare(strict_types=1);

return [
    'app_name' => 'bbs-v2',
    'app_url' => 'https://ayashii.world/cgi-bin/bbs-v2/public/',
    'url_mode' => 'index_php',
    'debug' => false,
    'timezone' => 'Asia/Tokyo',
    'database' => [
        'path' => __DIR__ . '/../storage/data/bbs.sqlite',
    ],
];
