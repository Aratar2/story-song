<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine;

use Doctrine\Common\Cache\Psr6\DoctrineProvider;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class EntityManagerFactory
{
    /**
     * @param array{dev_mode: bool, proxy_dir?: string|null, cache_dir?: string|null, metadata_dirs?: array<int, string>, connection: array<string, mixed>} $configuration
     */
    public function __construct(private array $configuration)
    {
    }

    public function createEntityManager(): EntityManagerInterface
    {
        $metadataDirs = $this->configuration['metadata_dirs'] ?? [];
        $proxyDir = $this->configuration['proxy_dir'] ?? null;
        $cacheDir = $this->configuration['cache_dir'] ?? null;
        $devMode = (bool) ($this->configuration['dev_mode'] ?? false);

        $cache = null;
        if ($cacheDir && !$devMode) {
            $this->ensureDirectory($cacheDir);
            $cache = DoctrineProvider::wrap(new FilesystemAdapter('doctrine', 0, $cacheDir));
        }

        $config = ORMSetup::createAttributeMetadataConfiguration($metadataDirs, $devMode, $proxyDir, $cache);

        $connectionParams = $this->configuration['connection'] ?? [];

        $databaseUrl = getenv('DATABASE_URL');
        if (is_string($databaseUrl) && $databaseUrl !== '') {
            $connectionParams = ['url' => $databaseUrl];
        } elseif (($connectionParams['driver'] ?? null) === 'pdo_sqlite' && isset($connectionParams['path'])) {
            $this->ensureDirectory(dirname((string) $connectionParams['path']));
        }

        return EntityManager::create($connectionParams, $config);
    }

    private function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0775, true);
        }
    }
}
