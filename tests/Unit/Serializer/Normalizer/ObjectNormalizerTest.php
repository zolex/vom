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

namespace Zolex\VOM\Test\Unit\Serializer\Normalizer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\BadMethodCallException;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\PropertyAccess\Exception\InvalidTypeException;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Zolex\VOM\Mapping\Normalizer;
use Zolex\VOM\Metadata\DenormalizerMetadata;
use Zolex\VOM\Metadata\Exception\MappingException;
use Zolex\VOM\Metadata\NormalizerMetadata;
use Zolex\VOM\Serializer\Factory\VersatileObjectMapperFactory;
use Zolex\VOM\Test\Fixtures\Booleans;
use Zolex\VOM\Test\Fixtures\DateAndTime;
use Zolex\VOM\Test\Fixtures\DummySerializer;
use Zolex\VOM\Test\Fixtures\Floats;
use Zolex\VOM\Test\Fixtures\IntProperty;
use Zolex\VOM\Test\Fixtures\MultiTypeProps;
use Zolex\VOM\Test\Fixtures\NestingRoot;
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
        $metadata->addDenormalizer(new DenormalizerMetadata(DateAndTime::class, 'nonExistentDenormalizerMethod', [], 'virtualName'));

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
        $metadata->addDenormalizer(new DenormalizerMetadata('AnotherClass', 'nonExistentDenormalizerMethod', [], 'virtualName'));

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
        $metadata->addNormalizer(new NormalizerMetadata('AnotherClass', 'nonExistentDenormalizerMethod', [], new Normalizer(), 'virtualName'));

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
        $metadata->addNormalizer(new NormalizerMetadata(DateAndTime::class, 'nonExistentNormalizerMethod', [], new Normalizer(), 'virtualName'));

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

    public function testValidateAndDenormalizeThrowsExceptionWithNonDenormalizerSerializer(): void
    {
        VersatileObjectMapperFactory::destroy();
        $objectNormalizer = VersatileObjectMapperFactory::getObjectNormalizer();
        $objectNormalizer->setSerializer(new DummySerializer());

        $data = [
            'LEVEL_ONE' => [
                'LEVEL_ONE_VALUE' => '1',
            ],
        ];

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('because injected serializer is not a denormalizer');
        $objectNormalizer->denormalize($data, NestingRoot::class);
    }

    public function testSupportsNormalizationWithNonObject(): void
    {
        VersatileObjectMapperFactory::destroy();
        $objectNormalizer = VersatileObjectMapperFactory::getObjectNormalizer();
        $this->assertFalse($objectNormalizer->supportsNormalization('string', null, ['vom' => true]));
        $this->assertFalse($objectNormalizer->supportsNormalization(123, null, ['vom' => true]));
        $this->assertFalse($objectNormalizer->supportsNormalization([], null, ['vom' => true]));
    }

    public function testSupportsNormalizationWithoutVomContext(): void
    {
        VersatileObjectMapperFactory::destroy();
        $objectNormalizer = VersatileObjectMapperFactory::getObjectNormalizer();
        $this->assertFalse($objectNormalizer->supportsNormalization(new DateAndTime(), null, []));
        $this->assertFalse($objectNormalizer->supportsNormalization(new DateAndTime(), null, ['vom' => false]));
    }

    public function testSupportsDenormalizationWithString(): void
    {
        VersatileObjectMapperFactory::destroy();
        $objectNormalizer = VersatileObjectMapperFactory::getObjectNormalizer();
        $this->assertTrue($objectNormalizer->supportsDenormalization('some string data', DateAndTime::class, null, ['vom' => true]));
    }

    public function testSupportsDenormalizationWithObject(): void
    {
        VersatileObjectMapperFactory::destroy();
        $objectNormalizer = VersatileObjectMapperFactory::getObjectNormalizer();
        $this->assertTrue($objectNormalizer->supportsDenormalization(new \stdClass(), DateAndTime::class, null, ['vom' => true]));
    }

    public function testSupportsDenormalizationWithoutVomContext(): void
    {
        VersatileObjectMapperFactory::destroy();
        $objectNormalizer = VersatileObjectMapperFactory::getObjectNormalizer();
        $this->assertFalse($objectNormalizer->supportsDenormalization([], DateAndTime::class, null, []));
        $this->assertFalse($objectNormalizer->supportsDenormalization([], DateAndTime::class, null, ['vom' => false]));
    }

    public function testSupportsDenormalizationWithInvalidType(): void
    {
        VersatileObjectMapperFactory::destroy();
        $objectNormalizer = VersatileObjectMapperFactory::getObjectNormalizer();
        // Class that has no metadata should not be supported
        $this->assertFalse($objectNormalizer->supportsDenormalization([], 'NonExistentClass', null, ['vom' => true]));
    }

    public function testGetSupportedTypes(): void
    {
        VersatileObjectMapperFactory::destroy();
        $objectNormalizer = VersatileObjectMapperFactory::getObjectNormalizer();
        $types = $objectNormalizer->getSupportedTypes(null);
        $this->assertIsArray($types);
        $this->assertArrayHasKey('object', $types);
        $this->assertArrayHasKey('*', $types);
        $this->assertTrue($types['object']);
        $this->assertTrue($types['*']);
    }

    public function testNormalizeThrowsWithNonNormalizerSerializer(): void
    {
        VersatileObjectMapperFactory::destroy();
        $objectNormalizer = VersatileObjectMapperFactory::getObjectNormalizer();
        $objectNormalizer->setSerializer(new DummySerializer());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The serializer must implement the NormalizerInterface');
        $objectNormalizer->normalize(new DateAndTime());
    }

    public function testDenormalizeXmlBoolFromString(): void
    {
        VersatileObjectMapperFactory::destroy();
        $objectNormalizer = VersatileObjectMapperFactory::getObjectNormalizer();

        $result = $objectNormalizer->denormalize(['bool' => '1', 'nullableBool' => '0'], Booleans::class, 'xml');
        $this->assertTrue($result->bool);
        $this->assertFalse($result->nullableBool);
    }

    public function testDenormalizeXmlBoolFromTrueFalseWords(): void
    {
        VersatileObjectMapperFactory::destroy();
        $objectNormalizer = VersatileObjectMapperFactory::getObjectNormalizer();

        $result = $objectNormalizer->denormalize(['bool' => 'true', 'nullableBool' => 'false'], Booleans::class, 'xml');
        $this->assertTrue($result->bool);
        $this->assertFalse($result->nullableBool);
    }

    public function testDenormalizeXmlInvalidBoolThrowsException(): void
    {
        VersatileObjectMapperFactory::destroy();
        $objectNormalizer = VersatileObjectMapperFactory::getObjectNormalizer();

        $this->expectException(NotNormalizableValueException::class);
        $this->expectExceptionMessage('The type of the "bool" attribute for class "Zolex\VOM\Test\Fixtures\Booleans" must be bool');
        $objectNormalizer->denormalize(['bool' => 'not-a-bool'], Booleans::class, 'xml');
    }

    public function testDenormalizeXmlFloatFromString(): void
    {
        VersatileObjectMapperFactory::destroy();
        $objectNormalizer = VersatileObjectMapperFactory::getObjectNormalizer();

        $result = $objectNormalizer->denormalize(['value' => '3.14'], Floats::class, 'xml');
        $this->assertEqualsWithDelta(3.14, $result->value, 0.001);
    }

    public function testDenormalizeXmlSpecialFloatValues(): void
    {
        VersatileObjectMapperFactory::destroy();
        $objectNormalizer = VersatileObjectMapperFactory::getObjectNormalizer();

        $nan = $objectNormalizer->denormalize(['value' => 'NaN'], Floats::class, 'xml');
        $this->assertNan($nan->value);

        $inf = $objectNormalizer->denormalize(['value' => 'INF'], Floats::class, 'xml');
        $this->assertEquals(\INF, $inf->value);

        $negInf = $objectNormalizer->denormalize(['value' => '-INF'], Floats::class, 'xml');
        $this->assertEquals(-\INF, $negInf->value);
    }

    public function testDenormalizeXmlInvalidFloatThrowsException(): void
    {
        VersatileObjectMapperFactory::destroy();
        $objectNormalizer = VersatileObjectMapperFactory::getObjectNormalizer();

        $this->expectException(NotNormalizableValueException::class);
        $this->expectExceptionMessage('The type of the "value" attribute for class "Zolex\VOM\Test\Fixtures\Floats" must be float');
        $objectNormalizer->denormalize(['value' => 'not-a-float'], Floats::class, 'xml');
    }

    public function testDenormalizeXmlIntFromString(): void
    {
        VersatileObjectMapperFactory::destroy();
        $objectNormalizer = VersatileObjectMapperFactory::getObjectNormalizer();

        $result = $objectNormalizer->denormalize(['value' => '42'], IntProperty::class, 'xml');
        $this->assertSame(42, $result->value);
    }

    public function testDenormalizeXmlInvalidIntThrowsException(): void
    {
        VersatileObjectMapperFactory::destroy();
        $objectNormalizer = VersatileObjectMapperFactory::getObjectNormalizer();

        $this->expectException(NotNormalizableValueException::class);
        $this->expectExceptionMessage('The type of the "value" attribute for class "Zolex\VOM\Test\Fixtures\IntProperty" must be int');
        $objectNormalizer->denormalize(['value' => 'not-an-int'], IntProperty::class, 'xml');
    }

    public function testDenormalizeXmlNullableFromEmptyString(): void
    {
        VersatileObjectMapperFactory::destroy();
        $objectNormalizer = VersatileObjectMapperFactory::getObjectNormalizer();

        $result = $objectNormalizer->denormalize(['nullableBool' => ''], Booleans::class, 'xml');
        $this->assertNull($result->nullableBool);
    }

    public function testDisableTypeEnforcementReturnsDataAsIs(): void
    {
        VersatileObjectMapperFactory::destroy();
        $objectNormalizer = VersatileObjectMapperFactory::getObjectNormalizer();

        // disable_type_enforcement bypasses VOM's type check (line 784) and returns
        // the raw value, but PHP's PropertyAccessor still enforces the declared type,
        // causing an InvalidTypeException when assigning a string to int|float|null.
        $this->expectException(InvalidTypeException::class);
        $objectNormalizer->denormalize(
            ['value' => 'not-a-number'],
            MultiTypeProps::class,
            null,
            ['disable_type_enforcement' => true]
        );
    }

    public function testExhaustedUnionTypeThrowsException(): void
    {
        VersatileObjectMapperFactory::destroy();
        $objectNormalizer = VersatileObjectMapperFactory::getObjectNormalizer();

        $this->expectException(NotNormalizableValueException::class);
        $objectNormalizer->denormalize(['value' => 'not-a-number'], MultiTypeProps::class);
    }

    public function testExtractFromStringReturnsNullWhenNoExtractorConfigured(): void
    {
        VersatileObjectMapperFactory::destroy();
        $objectNormalizer = VersatileObjectMapperFactory::getObjectNormalizer();

        // Passing a string as root data: extractFromString() is called for each property.
        // Without an extractor or serialized=true, it returns null, leaving the property unset.
        $result = $objectNormalizer->denormalize('raw-string-data', Booleans::class);
        $this->assertInstanceOf(Booleans::class, $result);
        $this->assertNull($result->nullableBool);
    }
}
