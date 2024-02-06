<?php

/*
 * This file is part of the VOM package.
 *
 * (c) Andreas Linden <zlx@gmx.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Metadata\Factory;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Zolex\VOM\Metadata\Factory\CachedModelMetadataFactory;
use Zolex\VOM\Metadata\Factory\ModelMetadataFactory;
use Zolex\VOM\PropertyInfo\Extractor\PropertyInfoExtractorFactory;
use Zolex\VOM\Test\Fixtures\Person;

class CachedResourceMetadataFactoryTest extends TestCase
{
    public function testLocalCache()
    {
        $cachePool = new ArrayAdapter();
        $resourceMetadataFactory = new ModelMetadataFactory(PropertyInfoExtractorFactory::create());
        $cachedResourceMetadataFactory = new CachedModelMetadataFactory($cachePool, $resourceMetadataFactory, true);

        $metadata = $cachedResourceMetadataFactory->getMetadataFor(Person::class);
        $metadata2 = $cachedResourceMetadataFactory->getMetadataFor(Person::class);
        $this->assertSame($metadata, $metadata2);
    }

    public function testPSRCache()
    {
        $cachePool = new ArrayAdapter();
        $modelMetadataFactory = new ModelMetadataFactory(PropertyInfoExtractorFactory::create());
        $cachedResourceMetadataFactory = new CachedModelMetadataFactory($cachePool, $modelMetadataFactory, true);

        $metadata = $cachedResourceMetadataFactory->getMetadataFor(Person::class);
        $cachedMetadata = $cachePool->getItem(CachedModelMetadataFactory::CACHE_KEY_PREFIX.md5(Person::class))->get();
        $this->assertEquals($metadata, $cachedMetadata);

        $modelMetadataFactory = new ModelMetadataFactory(PropertyInfoExtractorFactory::create());
        $cachedResourceMetadataFactory = new CachedModelMetadataFactory($cachePool, $modelMetadataFactory, true);

        $metadata2 = $cachedResourceMetadataFactory->getMetadataFor(Person::class);
        $this->assertEquals($cachedMetadata, $metadata2);
    }
}
