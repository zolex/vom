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

namespace Zolex\VOM\Test\Functional;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Zolex\VOM\Exception\InvalidArgumentException;
use Zolex\VOM\Metadata\Exception\MappingException;
use Zolex\VOM\Serializer\Factory\VersatileObjectMapperFactory;
use Zolex\VOM\Serializer\VersatileObjectMapper;
use Zolex\VOM\Test\Fixtures\ArgumentOnProperty;
use Zolex\VOM\Test\Fixtures\DateAndTime;
use Zolex\VOM\Test\Fixtures\DenormalizerWithoutArguments;
use Zolex\VOM\Test\Fixtures\DummyNormalizer;
use Zolex\VOM\Test\Fixtures\DummySerializer;
use Zolex\VOM\Test\Fixtures\Person;

class VersatileObjectMapperTest extends TestCase
{
    protected static VersatileObjectMapper $serializer;
    protected static ParameterBag $parameterBag;

    public static function setUpBeforeClass(): void
    {
        static::$parameterBag = new ParameterBag();
        static::$parameterBag->set('example', true);
        static::$serializer = VersatileObjectMapperFactory::create(null, [self::$parameterBag]);
    }

    public function testNormalizerException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The decorated serializer must implement the NormalizerInterface');
        new VersatileObjectMapper(new DummySerializer());
    }

    public function testDenormalizerException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The decorated serializer must implement the DenormalizerInterface');
        new VersatileObjectMapper(new DummyNormalizer());
    }

    public function testDenormalizerWithoutArgumentsThrowsException(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Denormalizer method Zolex\VOM\Test\Fixtures\DenormalizerWithoutArguments::denormalizeNothing() without arguments is useless. Consider adding VOM\Argument or removing VOM\Denormalizer.');
        static::$serializer->denormalize([], DenormalizerWithoutArguments::class);
    }

    public function testDecoratedMethods(): void
    {
        $serialized = static::$serializer->serialize([2], 'json', [1]);
        $this->assertEquals('[2]', $serialized);
        $deserialized = static::$serializer->deserialize('[]', DateAndTime::class, 'json');
        $this->assertEquals(new DateAndTime(), $deserialized);

        $supportedTypes = static::$serializer->getSupportedTypes('json');
        $this->assertEquals(['*' => false], $supportedTypes);

        $supportsNormalization = static::$serializer->supportsNormalization(new DateAndTime());
        $this->assertTrue($supportsNormalization);
        $normalized = static::$serializer->normalize(new \DateTime('2010-01-01 00:00:00'), 'json');
        $this->assertEquals('2010-01-01T00:00:00+00:00', $normalized);

        $supportsDenormalization = static::$serializer->supportsDenormalization(['dateTime' => '2010-01-01 10:10:10'], DateAndTime::class);
        $this->assertTrue($supportsDenormalization);

        $denormalized = static::$serializer->denormalize([2], DateAndTime::class);
        $this->assertEquals(new DateAndTime(), $denormalized);
    }

    public function testObjectToPopulate(): void
    {
        $person = Person::create(id: 666);

        $data = [
            'name' => [
                'firstname' => 'Peter',
                'lastname' => 'Parker',
            ],
        ];

        $person2 = static::$serializer->denormalize($data, Person::class, null, ['object_to_populate' => $person]);
        $this->assertSame($person, $person2);
        $this->assertEquals('Peter', $person2->firstname);
        $this->assertEquals('Parker', $person2->lastname);
    }

    public function testToObject(): void
    {
        $data = [
            (object) [
                'prop' => 'val',
            ],
            [
                'prop' => 'val',
                'another' => [
                    'prop' => 'val',
                ],
            ],
        ];

        $expected = [
            (object) [
                'prop' => 'val',
            ],
            (object) [
                'prop' => 'val',
                'another' => (object) [
                    'prop' => 'val',
                ],
            ],
        ];

        $obj = VersatileObjectMapper::toObject($data);
        $this->assertEquals($expected, $obj);
    }

    public function testArgumentOnPropertyThrowsException(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Attribute "Zolex\VOM\Mapping\Argument" cannot target property (allowed targets: parameter)');

        static::$serializer->denormalize([], ArgumentOnProperty::class);
    }
}
