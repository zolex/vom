<?php

/*
 * This file is part of the VOM package.
 *
 * (c) Andreas Linden <zlx@gmx.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zolex\VOM\Serializer\Factory;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer as SymfonyObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\UnwrappingDenormalizer;
use Symfony\Component\Serializer\Serializer;
use Zolex\VOM\Metadata\Factory\CachedModelMetadataFactory;
use Zolex\VOM\Metadata\Factory\ModelMetadataFactory;
use Zolex\VOM\PropertyInfo\Extractor\PropertyInfoExtractorFactory;
use Zolex\VOM\Serializer\Normalizer\BooleanNormalizer;
use Zolex\VOM\Serializer\Normalizer\CommonFlagNormalizer;
use Zolex\VOM\Serializer\Normalizer\ObjectNormalizer;
use Zolex\VOM\Serializer\VersatileObjectMapper;

/**
 * For use without symfony-framework, this just a convenient way to get a preconfigured VOM.
 */
class VersatileObjectMapperFactory
{
    public static function create(?CacheItemPoolInterface $cacheItemPool = null): VersatileObjectMapper
    {
        $propertyInfo = PropertyInfoExtractorFactory::create();
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $modelMetadataFactory = new ModelMetadataFactory($propertyInfo);
        if ($cacheItemPool) {
            $modelMetadataFactory = new CachedModelMetadataFactory($cacheItemPool, $modelMetadataFactory, true);
        }

        $serializer = new Serializer(
            [
                new UnwrappingDenormalizer(),
                new ObjectNormalizer($modelMetadataFactory, $propertyAccessor),
                new BooleanNormalizer(),
                new CommonFlagNormalizer(),
                new DateTimeNormalizer(),
                new JsonSerializableNormalizer(),
                new ArrayDenormalizer(),
                new SymfonyObjectNormalizer(),
            ],
            [new JsonEncoder()]
        );

        return new VersatileObjectMapper($serializer);
    }
}
