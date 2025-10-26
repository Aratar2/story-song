<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine;

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
            $cache = new FilesystemAdapter('doctrine', 0, $cacheDir);
        }

        $config = ORMSetup::createAttributeMetadataConfiguration($metadataDirs, $devMode, $proxyDir, $cache);

        $connectionParams = $this->configuration['connection'] ?? [];

        $databaseUrl = getenv('DATABASE_URL');
        if (is_string($databaseUrl) && $databaseUrl !== '') {
            $resolvedUrl = $this->resolveDatabaseUrl($databaseUrl);
            if ($resolvedUrl !== null) {
                $connectionParams = ['url' => $resolvedUrl];
                $this->prepareSqliteDirectoryFromUrl($resolvedUrl);
            }
        }

        if (($connectionParams['driver'] ?? null) === 'pdo_sqlite' && isset($connectionParams['path'])) {
            $this->ensureDirectory(dirname((string) $connectionParams['path']));
        } elseif (isset($connectionParams['url'])) {
            $this->prepareSqliteDirectoryFromUrl((string) $connectionParams['url']);
        }

        return EntityManager::create($connectionParams, $config);
    }

    private function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0775, true);
        }
    }

    private function resolveDatabaseUrl(string $databaseUrl): ?string
    {
        if ($databaseUrl === '') {
            return null;
        }

        if (!str_contains($databaseUrl, '%kernel.project_dir%')) {
            return $databaseUrl;
        }

        $projectDir = dirname(__DIR__, 3);

        return $projectDir !== '' ? str_replace('%kernel.project_dir%', $projectDir, $databaseUrl) : null;
    }

    private function prepareSqliteDirectoryFromUrl(string $url): void
    {
        if (!str_starts_with($url, 'sqlite:')) {
            return;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path) || $path === '' || $path === ':memory:') {
            return;
        }

        $this->ensureDirectory(dirname($path));
    }
}
