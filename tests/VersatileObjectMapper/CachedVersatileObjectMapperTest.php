<?php

namespace Zolex\VOM\Test\VersatileObjectMapper;

use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Zolex\VOM\Metadata\Factory\CachedModelMetadataFactory;
use Zolex\VOM\Metadata\Factory\ModelMetadataFactory;
use Zolex\VOM\Symfony\PropertyInfo\PropertyInfoExtractorFactory;
use Zolex\VOM\Test\Fixtures\DateAndTime;
use Zolex\VOM\VersatileObjectMapper;

class CachedVersatileObjectMapperTest extends VersatileObjectMapperTest
{
    public function setUp(): void
    {
        $cachePool = new ArrayAdapter();
        $modelMetadataFactory = new ModelMetadataFactory(PropertyInfoExtractorFactory::create());
        $cachedMetadataFactory = new CachedModelMetadataFactory($cachePool, $modelMetadataFactory, true);

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->objectMapper = new VersatileObjectMapper($cachedMetadataFactory, $propertyAccessor);
    }

    public function testDateTime()
    {
        $model = $this->objectMapper->denormalize([], DateAndTime::class);
        $this->assertEquals(new DateAndTime(), $model);

        $model = $this->objectMapper->denormalize(['dateTime' => null, 'dateTimeImmutable' => null], DateAndTime::class);
        $this->assertEquals(new DateAndTime(), $model);

        $model = $this->objectMapper->denormalize([
            'dateTime' => '2024-01-20 06:00:00',
            'dateTimeImmutable' => '2001-04-11 14:14:00',
        ], DateAndTime::class);

        $expected = new DateAndTime();
        $expected->dateTime = new \DateTime('2024-01-20 06:00:00');
        $expected->dateTimeImmutable = new \DateTimeImmutable('2001-04-11 14:14:00');
        $this->assertEquals($expected, $model);
    }
}
