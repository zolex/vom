<?php

/*
 * This file is part of the VOM package.
 *
 * (c) Andreas Linden <zlx@gmx.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zolex\VOM\Test\VersatileObjectMapper;

use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Zolex\VOM\Metadata\Factory\CachedModelMetadataFactory;
use Zolex\VOM\Metadata\Factory\ModelMetadataFactory;
use Zolex\VOM\Symfony\PropertyInfo\PropertyInfoExtractorFactory;
use Zolex\VOM\VersatileObjectMapper;

class CachedVersatileObjectMapperTest extends VersatileObjectMapperTest
{
    protected function setUp(): void
    {
        $cachePool = new ArrayAdapter();
        $modelMetadataFactory = new ModelMetadataFactory(PropertyInfoExtractorFactory::create());
        $cachedMetadataFactory = new CachedModelMetadataFactory($cachePool, $modelMetadataFactory, true);

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->objectMapper = new VersatileObjectMapper($cachedMetadataFactory, $propertyAccessor);

        /*
        $encoders = [new JsonEncoder()];
        $normalizers = [new UnwrappingDenormalizer(), new DateTimeNormalizer(), new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);
        $this->objectMapper->setNormalizer($serializer);
        $this->objectMapper->setDenormalizer($serializer);
        */
    }
}
