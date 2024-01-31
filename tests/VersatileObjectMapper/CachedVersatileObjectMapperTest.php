<?php

namespace Zolex\VOM\Test\VersatileObjectMapper;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Zolex\VOM\Metadata\Factory\CachedModelMetadataFactory;
use Zolex\VOM\Metadata\Factory\ModelMetadataFactory;
use Zolex\VOM\Metadata\Factory\PropertyMetadataFactory;
use Zolex\VOM\Test\Fixtures\Address;
use Zolex\VOM\Test\Fixtures\Arrays;
use Zolex\VOM\Test\Fixtures\Booleans;
use Zolex\VOM\Test\Fixtures\DateAndTime;
use Zolex\VOM\Test\Fixtures\FlagParent;
use Zolex\VOM\Test\Fixtures\Person;
use Zolex\VOM\Test\Fixtures\SickChild;
use Zolex\VOM\Test\Fixtures\SickRoot;
use Zolex\VOM\Test\Fixtures\SickSack;
use Zolex\VOM\Test\Fixtures\SickSuck;
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
