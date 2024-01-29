<?php

declare(strict_types=1);

namespace Zolex\VOM\Metadata\Factory;

use Psr\Cache\CacheException;
use Psr\Cache\CacheItemPoolInterface;
use Zolex\VOM\Metadata\PropertyMetadata;

class CachedPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    public const CACHE_KEY_PREFIX = 'vom_property_metadata_';
    private array $localCache = [];

    public function __construct(
        private readonly CacheItemPoolInterface $cacheItemPool,
        private readonly PropertyMetadataFactoryInterface $decorated,
        private readonly bool $cachePoolEnabled,
    ) {
    }

    public function create(
        \ReflectionProperty $reflectionProperty,
        \ReflectionClass $reflectionClass,
        ?PropertyMetadata $parentPropertyMetadata,
    ): ?PropertyMetadata {
        $uid = random_bytes(32);
        $cacheKey = self::CACHE_KEY_PREFIX.md5($uid);
        if (\array_key_exists($cacheKey, $this->localCache)) {
            return $this->localCache[$cacheKey];
        }

        if ($this->cachePoolEnabled) {
            try {
                $cacheItem = $this->cacheItemPool->getItem($cacheKey);
            } catch (CacheException) {
                return $this->localCache[$cacheKey] = $this->decorated->create($class);
            }

            if ($cacheItem->isHit()) {
                return $this->localCache[$cacheKey] = $cacheItem->get();
            }
        }

        $this->localCache[$cacheKey] = $this->decorated->create($reflectionProperty, $reflectionClass);

        if ($this->cachePoolEnabled) {
            $cacheItem->set($this->localCache[$cacheKey]);
            $this->cacheItemPool->save($cacheItem);
        }

        return $this->localCache[$cacheKey];
    }
}
