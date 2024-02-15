<?php

/*
 * This file is part of the VOM package.
 *
 * (c) Andreas Linden <zlx@gmx.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zolex\VOM\Test\Serializer\Normalizer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\BadMethodCallException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Zolex\VOM\Mapping\Normalizer;
use Zolex\VOM\Metadata\DenormalizerMetadata;
use Zolex\VOM\Metadata\Exception\MappingException;
use Zolex\VOM\Metadata\NormalizerMetadata;
use Zolex\VOM\Serializer\Factory\VersatileObjectMapperFactory;
use Zolex\VOM\Test\Fixtures\DateAndTime;
use Zolex\VOM\Test\Fixtures\Thing;

class ObjectNormalizerTest extends TestCase
{
    public function testSupportsNormalization(): void
    {
        VersatileObjectMapperFactory::destroy();
        $objectNormalizer = VersatileObjectMapperFactory::getObjectNormalizer();
        $this->assertFalse($objectNormalizer->supportsNormalization(new DateAndTime()));
        $this->assertTrue($objectNormalizer->supportsNormalization(new DateAndTime(), null, ['vom' => true]));
    }

    public function testSupportsDenormalization(): void
    {
        VersatileObjectMapperFactory::destroy();
        $objectNormalizer = VersatileObjectMapperFactory::getObjectNormalizer();
        $this->assertFalse($objectNormalizer->supportsDenormalization([], DateAndTime::class));
        $this->assertTrue($objectNormalizer->supportsDenormalization([], DateAndTime::class, null, ['vom' => true]));
    }

    public function testNormalizeNull(): void
    {
        VersatileObjectMapperFactory::destroy();
        $objectNormalizer = VersatileObjectMapperFactory::getObjectNormalizer();
        $this->assertNull($objectNormalizer->normalize(null));
    }

    public function testDenormalizeNull(): void
    {
        VersatileObjectMapperFactory::destroy();
        $objectNormalizer = VersatileObjectMapperFactory::getObjectNormalizer();
        $this->assertNull($objectNormalizer->denormalize(null, DateAndTime::class));
    }

    public function testUncallableDenormalizerThrowsException(): void
    {
        VersatileObjectMapperFactory::destroy();
        $metadataFactory = VersatileObjectMapperFactory::getMetadataFactory();
        $objectNormalizer = VersatileObjectMapperFactory::getObjectNormalizer();
        $metadata = $metadataFactory->getMetadataFor(DateAndTime::class);
        $metadata->addDenormalizer(new DenormalizerMetadata([DateAndTime::class, 'nonExistentDenormalizerMethod'], [], 'virtualName'));

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Bad denormalizer method call: Call to undefined method Zolex\VOM\Test\Fixtures\DateAndTime::nonExistentDenormalizerMethod()');
        $objectNormalizer->denormalize([], DateAndTime::class);
    }

    public function testMismatchingDenormalizerClassThrowsException(): void
    {
        VersatileObjectMapperFactory::destroy();
        $metadataFactory = VersatileObjectMapperFactory::getMetadataFactory();
        $objectNormalizer = VersatileObjectMapperFactory::getObjectNormalizer();
        $metadata = $metadataFactory->getMetadataFor(DateAndTime::class);
        $metadata->addDenormalizer(new DenormalizerMetadata(['AnotherClass', 'nonExistentDenormalizerMethod'], [], 'virtualName'));

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Model class "Zolex\VOM\Test\Fixtures\DateAndTime" does not match the expected denormalizer class "AnotherClass".');
        $objectNormalizer->denormalize([], DateAndTime::class);
    }

    public function testMismatchingNormalizerClassThrowsException(): void
    {
        VersatileObjectMapperFactory::destroy();
        $metadataFactory = VersatileObjectMapperFactory::getMetadataFactory();
        $objectNormalizer = VersatileObjectMapperFactory::getObjectNormalizer();
        $metadata = $metadataFactory->getMetadataFor(DateAndTime::class);
        $metadata->addNormalizer(new NormalizerMetadata(['AnotherClass', 'nonExistentDenormalizerMethod'], 'virtualName', new Normalizer()));

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Model class "Zolex\VOM\Test\Fixtures\DateAndTime" does not match the expected normalizer class "AnotherClass".');
        $objectNormalizer->normalize(new DateAndTime());
    }

    public function testUncallableNormalizerThrowsException(): void
    {
        VersatileObjectMapperFactory::destroy();
        $metadataFactory = VersatileObjectMapperFactory::getMetadataFactory();
        $objectNormalizer = VersatileObjectMapperFactory::getObjectNormalizer();
        $metadata = $metadataFactory->getMetadataFor(DateAndTime::class);
        $metadata->addNormalizer(new NormalizerMetadata([DateAndTime::class, 'nonExistentNormalizerMethod'], 'virtualName', new Normalizer()));

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Bad normalizer method call: Call to undefined method Zolex\VOM\Test\Fixtures\DateAndTime::nonExistentNormalizerMethod()');
        $objectNormalizer->normalize(new DateAndTime());
    }

    public function testMissingDiscriminatorThrowsException(): void
    {
        VersatileObjectMapperFactory::destroy();
        $objectNormalizer = VersatileObjectMapperFactory::getObjectNormalizer();
        $this->expectException(NotNormalizableValueException::class);
        $this->expectExceptionMessage('Type property "type" not found for the abstract object "Zolex\VOM\Test\Fixtures\Thing"');
        $objectNormalizer->denormalize([], Thing::class);
    }

    public function testWrongDiscriminatorThrowsException(): void
    {
        VersatileObjectMapperFactory::destroy();
        $objectNormalizer = VersatileObjectMapperFactory::getObjectNormalizer();
        $this->expectException(NotNormalizableValueException::class);
        $this->expectExceptionMessage('The type "doesnt-exist" is not a valid value.');
        $objectNormalizer->denormalize(['type' => 'doesnt-exist'], Thing::class);
    }
}
