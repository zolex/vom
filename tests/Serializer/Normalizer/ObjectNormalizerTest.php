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
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Zolex\VOM\Metadata\DenormalizerMetadata;
use Zolex\VOM\Metadata\Factory\Exception\MappingException;
use Zolex\VOM\Metadata\NormalizerMetadata;
use Zolex\VOM\Serializer\Factory\VersatileObjectMapperFactory;
use Zolex\VOM\Serializer\Normalizer\ObjectNormalizer;
use Zolex\VOM\Test\Fixtures\DateAndTime;
use Zolex\VOM\Test\Fixtures\Thing;

class ObjectNormalizerTest extends TestCase
{
    public static ObjectNormalizer $objectNormalizer;

    protected function setUp(): void
    {
        self::$objectNormalizer = VersatileObjectMapperFactory::getObjectNormalizer();
    }

    public function testSupportsNormalization(): void
    {
        $this->assertFalse(self::$objectNormalizer->supportsNormalization(new DateAndTime()));
        $this->assertTrue(self::$objectNormalizer->supportsNormalization(new DateAndTime(), null, ['vom' => true]));
    }

    public function testSupportsDenormalization(): void
    {
        $this->assertFalse(self::$objectNormalizer->supportsDenormalization([], DateAndTime::class));
        $this->assertTrue(self::$objectNormalizer->supportsDenormalization([], DateAndTime::class, null, ['vom' => true]));
    }

    public function testNormalizeNull(): void
    {
        $this->assertNull(self::$objectNormalizer->normalize(null));
    }

    public function testDenormalizeNull(): void
    {
        $this->assertNull(self::$objectNormalizer->denormalize(null, DateAndTime::class));
    }

    public function testUncallableDenormalizerThrowsException(): void
    {
        $metadataFactory = VersatileObjectMapperFactory::getMetadataFactory();
        $metadata = $metadataFactory->getMetadataFor(DateAndTime::class);
        $metadata->addDenormalizer(new DenormalizerMetadata('nonExistentDenormalizerMethod', []));

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Unable to denormalize: Call to undefined method Zolex\VOM\Test\Fixtures\DateAndTime::nonExistentDenormalizerMethod()');
        self::$objectNormalizer->denormalize([], DateAndTime::class);
    }

    public function testUncallableNormalizerThrowsException(): void
    {
        $metadataFactory = VersatileObjectMapperFactory::getMetadataFactory();
        $metadata = $metadataFactory->getMetadataFor(DateAndTime::class);
        $metadata->addNormalizer(new NormalizerMetadata('nonExistentNormalizerMethod'));

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Unable to normalize: Call to undefined method Zolex\VOM\Test\Fixtures\DateAndTime::nonExistentNormalizerMethod()');
        self::$objectNormalizer->normalize(new DateAndTime());
    }

    public function testMissingDiscriminatorThrowsException(): void
    {
        $this->expectException(NotNormalizableValueException::class);
        $this->expectExceptionMessage('Type property "type" not found for the abstract object "Zolex\VOM\Test\Fixtures\Thing"');
        self::$objectNormalizer->denormalize([], Thing::class);
    }

    public function testWrongDiscriminatorThrowsException(): void
    {
        $this->expectException(NotNormalizableValueException::class);
        $this->expectExceptionMessage('The type "doesnt-exist" is not a valid value.');
        self::$objectNormalizer->denormalize(['type' => 'doesnt-exist'], Thing::class);
    }
}
