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

namespace Zolex\VOM\Test\Unit\Serializer\Factory;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Zolex\VOM\Metadata\Factory\CachedModelMetadataFactory;
use Zolex\VOM\Serializer\Factory\VersatileObjectMapperFactory;
use Zolex\VOM\Serializer\Normalizer\ObjectNormalizer;

class VersatileObjectMapperFactoryTest extends TestCase
{
    public function testCreateObjectNormalizerWithDeps(): void
    {
        $dep = new \stdClass();
        $normalizer = VersatileObjectMapperFactory::createObjectNormalizer(null, [$dep]);
        $this->assertInstanceOf(ObjectNormalizer::class, $normalizer);
    }

    public function testCreateObjectNormalizerWithCachePool(): void
    {
        $cache = new ArrayAdapter();
        VersatileObjectMapperFactory::createObjectNormalizer($cache);
        $metadataFactory = VersatileObjectMapperFactory::getMetadataFactory($cache);
        $this->assertInstanceOf(CachedModelMetadataFactory::class, $metadataFactory);
    }

    public function testCreateObjectNormalizerWithDepsAndCachePool(): void
    {
        $dep = new \stdClass();
        $cache = new ArrayAdapter();
        $normalizer = VersatileObjectMapperFactory::createObjectNormalizer($cache, [$dep]);
        $this->assertInstanceOf(ObjectNormalizer::class, $normalizer);
        $this->assertInstanceOf(CachedModelMetadataFactory::class, VersatileObjectMapperFactory::getMetadataFactory());
    }
}
