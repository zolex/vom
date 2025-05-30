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
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Zolex\VOM\Metadata\Exception\MissingTypeException;
use Zolex\VOM\Test\Fixtures\Booleans;
use Zolex\VOM\Test\Fixtures\DateAndTime;
use Zolex\VOM\Test\Fixtures\Floats;
use Zolex\VOM\Test\Fixtures\MixedProperty;
use Zolex\VOM\Test\Fixtures\MultiTypeProps;
use Zolex\VOM\Test\Functional\Standard\VersatileObjectMapperTestCase;

/**
 * @mixin VersatileObjectMapperTestCase
 */
class DataTypesTestCase extends TestCase
{
    public function testDenormalizeUnionType(): void
    {
        $model = static::$serializer->denormalize(['value' => 42], MultiTypeProps::class, null, ['disable_type_enforcement' => true]);
        $this->assertEquals(42, $model->value);

        $model = static::$serializer->denormalize(['value' => 13.37], MultiTypeProps::class, null, ['disable_type_enforcement' => true]);
        $this->assertEquals(13.37, $model->value);
    }

    public function testJsonNumberToFloat(): void
    {
        $data = ['value' => 3];

        /* @var Floats $floats */
        $floats = static::$serializer->denormalize($data, Floats::class, 'json');
        $normalized = static::$serializer->normalize($floats);
        $this->assertEquals($data, $normalized);
    }

    public function testMixed(): void
    {
        $this->expectException(MissingTypeException::class);
        $this->expectExceptionMessage('Could not determine the type of property "mixed" on class "Zolex\VOM\Test\Fixtures\MixedProperty".');
        static::$serializer->denormalize([], MixedProperty::class);
    }

    /**
     * @dataProvider provideBooleans
     */
    public function testBooleans($data, $expected): void
    {
        /* @var Booleans $booleans */
        $booleans = static::$serializer->denormalize($data, Booleans::class);
        $normalized = static::$serializer->normalize($booleans);
        $this->assertEquals($expected, $normalized);
    }

    public function testStrictBoolWithInvalidValueThrowsException(): void
    {
        $data = [
            'anotherBool' => 'SOMETHING_INVALID',
        ];

        $this->expectException(NotNormalizableValueException::class);
        $this->expectExceptionMessage('string(17) "SOMETHING_INVALID" on attribute "anotherBool" for class "Zolex\VOM\Test\Fixtures\Booleans" could not be normalized to a boolean and the property is not nullable. Check the VOM Property config and/or the data to be normalized');
        static::$serializer->denormalize($data, Booleans::class);
    }

    public function testInvalidTypeThrowsException(): void
    {
        $this->expectException(NotNormalizableValueException::class);
        static::$serializer->denormalize(['bool' => new \stdClass()], Booleans::class);
    }

    public function testNullableBooleans(): void
    {
        $data = [];

        /* @var Booleans $booleans */
        $booleans = static::$serializer->denormalize($data, Booleans::class);
        $this->assertFalse(isset($booleans->bool));
        $this->assertFalse(isset($booleans->nullableBool));

        $normalized = static::$serializer->normalize($booleans);
        $this->assertArrayNotHasKey('bool', $normalized);
        $this->assertArrayHasKey('nullableBool', $normalized);
        $this->assertNull($normalized['nullableBool']);
    }

    public function testNullableBooleanExplicitlyNull(): void
    {
        $data = [
            'nullableBool' => null,
        ];

        /* @var Booleans $booleans */
        $booleans = static::$serializer->denormalize($data, Booleans::class);
        $this->assertFalse(isset($booleans->bool));
        $this->assertNull($booleans->nullableBool);
    }

    public function testDateAndTime(): void
    {
        $data = [];

        /* @var DateAndTime $dateAndTime */
        $dateAndTime = static::$serializer->denormalize($data, DateAndTime::class);
        $this->assertFalse(isset($dateAndTime->dateTime));
        $this->assertFalse(isset($dateAndTime->dateTimeImmutable));

        $data = [
            'dateTime' => '2024-02-03 13:05:00',
            'dateTimeImmutable' => '1985-01-20 12:34:56',
        ];

        /* @var DateAndTime $dateAndTime */
        $dateAndTime = static::$serializer->denormalize($data, DateAndTime::class);
        $this->assertTrue(isset($dateAndTime->dateTime));
        $this->assertTrue(isset($dateAndTime->dateTimeImmutable));

        $this->assertEquals($data['dateTime'], $dateAndTime->dateTime->format('Y-m-d H:i:s'));
        $this->assertEquals($data['dateTimeImmutable'], $dateAndTime->dateTimeImmutable->format('Y-m-d H:i:s'));
    }

    public static function provideBooleans(): iterable
    {
        yield [
            [
                // bool has no explicit true-value configured
                'bool' => 1,
                'nullableBool' => 0,
                'stringBool' => 'yeah',
                'anotherBool' => 'FALSE',
            ],
            [
                // to the result will be true for anything in the default true-values list
                'bool' => true,
                'nullableBool' => false,
                'stringBool' => 'yeah',
                'anotherBool' => 'FALSE',
            ],
        ];

        yield [
            [
                'bool' => true,
                'nullableBool' => 'NO',
                'stringBool' => 'nope',
                'anotherBool' => 'TRUE',
            ],
            [
                'bool' => true,
                'nullableBool' => false,
                'stringBool' => 'nope',
                'anotherBool' => 'TRUE',
            ],
        ];

        yield [
            [
                'nullableBool' => 'INVALID_VALUE_FOR_NULLABLE_BOOL',
            ],
            [
                'nullableBool' => null,
            ],
        ];

        yield [
            [],
            [
                // only bools that are nullable can be null :P
                // rest must be uninitialized
                'nullableBool' => null,
            ],
        ];
    }
}
