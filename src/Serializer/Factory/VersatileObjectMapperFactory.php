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

namespace Zolex\VOM\Serializer\Factory;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorFromClassMetadata;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer as SymfonyObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\UnwrappingDenormalizer;
use Symfony\Component\Serializer\Serializer;
use Zolex\VOM\Metadata\Factory\CachedModelMetadataFactory;
use Zolex\VOM\Metadata\Factory\ModelMetadataFactory;
use Zolex\VOM\Metadata\Factory\ModelMetadataFactoryInterface;
use Zolex\VOM\PropertyInfo\Extractor\PropertyInfoExtractorFactory;
use Zolex\VOM\Serializer\Normalizer\ObjectNormalizer;
use Zolex\VOM\Serializer\VersatileObjectMapper;

/**
 * For use without symfony-framework, this just a convenient way to get a preconfigured VOM.
 */
class VersatileObjectMapperFactory
{
    private static ?ObjectNormalizer $objectNormalizer = null;
    private static ?ModelMetadataFactoryInterface $metadataFactory = null;

    public static function destroy()
    {
        self::$metadataFactory = null;
        self::$objectNormalizer = null;
    }

    public static function create(?CacheItemPoolInterface $cacheItemPool = null): VersatileObjectMapper
    {
        self::$objectNormalizer = self::createObjectNormalizer($cacheItemPool);

        $serializer = new Serializer(
            [
                new UnwrappingDenormalizer(),
                self::$objectNormalizer,
                new DateTimeNormalizer(),
                new JsonSerializableNormalizer(),
                new ArrayDenormalizer(),
                new SymfonyObjectNormalizer(),
            ],
            [new JsonEncoder()]
        );

        return new VersatileObjectMapper($serializer);
    }

    public static function createObjectNormalizer(?CacheItemPoolInterface $cacheItemPool = null): ObjectNormalizer
    {
        $propertyInfo = PropertyInfoExtractorFactory::create();
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        self::$metadataFactory = new ModelMetadataFactory($propertyInfo);
        if ($cacheItemPool) {
            self::$metadataFactory = new CachedModelMetadataFactory($cacheItemPool, self::$metadataFactory);
        }

        $classMetadataFactory = new ClassMetadataFactory(new AttributeLoader());
        $classDiscriminatorResolver = new ClassDiscriminatorFromClassMetadata($classMetadataFactory);

        return new ObjectNormalizer(self::$metadataFactory, $propertyAccessor, $classMetadataFactory, $classDiscriminatorResolver);
    }

    public static function getObjectNormalizer(?CacheItemPoolInterface $cacheItemPool = null): ObjectNormalizer
    {
        if (!self::$objectNormalizer) {
            self::create($cacheItemPool);
        }

        return self::$objectNormalizer;
    }

    public static function getMetadataFactory(?CacheItemPoolInterface $cacheItemPool = null): ModelMetadataFactoryInterface
    {
        if (!self::$objectNormalizer) {
            self::create($cacheItemPool);
        }

        return self::$metadataFactory;
    }
}
