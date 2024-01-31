<?php

namespace Zolex\VOM\Test\VersatileObjectMapper;

use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Zolex\VOM\Metadata\Factory\CachedModelMetadataFactory;
use Zolex\VOM\Metadata\Factory\ModelMetadataFactory;
use Zolex\VOM\Metadata\Factory\PropertyMetadataFactory;
use Zolex\VOM\VersatileObjectMapper;

class CachedVersatileObjectMapperTest extends VersatileObjectMapperTest
{
    public function setUp(): void
    {
        $cachePool = new ArrayAdapter();
        $modelMetadataFactory = new ModelMetadataFactory(new PropertyMetadataFactory());
        $cachedMetadataFactory = new CachedModelMetadataFactory($cachePool, $modelMetadataFactory, true);

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->objectMapper = new VersatileObjectMapper($cachedMetadataFactory, $propertyAccessor);
    }
}
