<?php

declare(strict_types=1);

namespace Zolex\VOM\Metadata\Factory;

use Psr\Cache\CacheException;
use Psr\Cache\CacheItemPoolInterface;
use Zolex\VOM\Metadata\ModelMetadata;
use Zolex\VOM\Metadata\PropertyMetadata;

class CachedModelMetadataFactory implements ModelMetadataFactoryInterface
{
    public const CACHE_KEY_PREFIX = 'vom_model_metadata_';
    private array $localCache = [];

    public function __construct(
        private readonly CacheItemPoolInterface $cacheItemPool,
        private readonly ModelMetadataFactoryInterface $decorated,
        private readonly bool $cachePoolEnabled,
    ) {
    }

    public function create(string $class, ?PropertyMetadata $parentPropertyMetadata = null): ?ModelMetadata
    {
        $cacheKey = self::CACHE_KEY_PREFIX.md5($class);
        if (\array_key_exists($cacheKey, $this->localCache)) {
            return $this->localCache[$cacheKey];
        }

        if ($this->cachePoolEnabled) {
            try {
                $cacheItem = $this->cacheItemPool->getItem($cacheKey);
                if ($cacheItem->isHit()) {
                    return $this->localCache[$cacheKey] = $cacheItem->get();
                }
            } catch (CacheException) {
                $x = 1;
            }
        }

        $this->localCache[$cacheKey] = $this->decorated->create($class);
        if ($this->cachePoolEnabled && isset($cacheItem)) {
            $cacheItem->set($this->localCache[$cacheKey]);
            $this->cacheItemPool->save($cacheItem);
        }

        return $this->localCache[$cacheKey];
    }
}
