<?php

declare(strict_types=1);

/*
 * This file is part of the VOM package.
 *
 * (c) Andreas Linden <zlx@gmx.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zolex\VOM\Metadata\Factory;

use Psr\Cache\CacheException;
use Psr\Cache\CacheItemPoolInterface;
use Zolex\VOM\Metadata\ModelMetadata;

class CachedModelMetadataFactory implements ModelMetadataFactoryInterface
{
    public const CACHE_KEY_PREFIX = 'vom_model_metadata_';
    private array $localCache = [];

    public function __construct(
        private readonly CacheItemPoolInterface $cacheItemPool,
        private readonly ModelMetadataFactoryInterface $decorated,
    ) {
    }

    public function getMetadataFor(string $class): ?ModelMetadata
    {
        $cacheKey = self::CACHE_KEY_PREFIX.md5($class);
        if (\array_key_exists($cacheKey, $this->localCache)) {
            return $this->localCache[$cacheKey];
        }

        $cacheItem = $this->cacheItemPool->getItem($cacheKey);
        if ($cacheItem->isHit()) {
            return $this->localCache[$cacheKey] = $cacheItem->get();
        }

        $this->localCache[$cacheKey] = $this->decorated->getMetadataFor($class);
        if (isset($cacheItem)) {
            $cacheItem->set($this->localCache[$cacheKey]);
            $this->cacheItemPool->save($cacheItem);
        }

        return $this->localCache[$cacheKey];
    }
}
