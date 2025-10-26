<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);

return [
    'dev_mode' => (getenv('APP_ENV') ?: 'dev') !== 'prod',
    'proxy_dir' => $projectRoot . '/var/cache/doctrine/proxies',
    'cache_dir' => $projectRoot . '/var/cache/doctrine',
    'metadata_dirs' => [
        $projectRoot . '/src/Entity',
    ],
    'connection' => [
        'driver' => 'pdo_sqlite',
        'path' => $projectRoot . '/var/data/story-song.sqlite',
    ],
];
