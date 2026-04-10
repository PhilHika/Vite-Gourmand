<?php

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

class QueryCacheService
{
    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly LoggerInterface $logger,
        private readonly string $environment = 'prod'
    )
    {
    }

    /**
     * Récupère un résultat en cache ou l'exécute
     *
     * Exemple :
     *   $menus = $this->cache->getOrFetch(
     *       'menus_all',
     *       fn() => $this->findAll(),
     *       1800  // 30 min
     *   );
     */
    public function getOrFetch(
        string $cacheKey,
        \Closure $fetchCallback,
        int $ttl = 3600
    ): mixed {
        // Pas de cache en dev
        if ($this->environment === 'dev') {
            return $fetchCallback();
        }

        $item = $this->cache->getItem($cacheKey);

        if ($item->isHit()) {
            $this->logger->debug('Cache HIT', ['key' => $cacheKey]);
            return $item->get();
        }

        // Cache MISS : exécute le callback (requête SQL)
        $this->logger->debug('Cache MISS', ['key' => $cacheKey]);
        $result = $fetchCallback();

        // Sauvegarde en cache
        $item->set($result);
        $item->expiresAfter($ttl);
        $this->cache->save($item);

        return $result;
    }

    /**
     * Invalide une clé de cache
     */
    public function invalidate(string $cacheKey): void
    {
        $this->cache->deleteItem($cacheKey);
        $this->logger->info('Cache invalidated', ['key' => $cacheKey]);
    }

    /**
     * Invalide plusieurs clés
     */
    public function invalidateMultiple(array $cacheKeys): void
    {
        $this->cache->deleteItems($cacheKeys);
        $this->logger->info('Cache invalidated (bulk)', ['keys' => $cacheKeys]);
    }

    /**
     * Vide tout le cache (⚠️ À utiliser avec prudence)
     */
    public function clear(): void
    {
        $this->cache->clear();
        $this->logger->warning('Cache cleared entirely');
    }
}
