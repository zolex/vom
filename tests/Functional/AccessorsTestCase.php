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
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Zolex\VOM\Metadata\Exception\MappingException;
use Zolex\VOM\Test\Fixtures\AccessorList;
use Zolex\VOM\Test\Fixtures\AccessorListGenericType;
use Zolex\VOM\Test\Fixtures\AccessorListWithWrongAccessors;
use Zolex\VOM\Test\Fixtures\FirstAndLastname;
use Zolex\VOM\Test\Fixtures\FirstAndLastnameObject;
use Zolex\VOM\Test\Functional\Standard\VersatileObjectMapperTestCase;

/**
 * @mixin VersatileObjectMapperTestCase
 */
abstract class AccessorsTestCase extends TestCase
{
    public function testAccessor(): void
    {
        $data = [
            'nested' => [
                'firstname' => 'Andreas',
                'deeper' => [
                    'surname' => 'Linden',
                ],
            ],
        ];

        /* @var FirstAndLastname $nestedName */
        $nestedName = static::$serializer->denormalize($data, FirstAndLastname::class);
        $this->assertEquals($data['nested']['firstname'], $nestedName->firstname);
        $this->assertEquals($data['nested']['deeper']['surname'], $nestedName->lastname);
    }

    public function testObjectAccessorThrowsExceptionForNormalization(): void
    {
        $data = [
            'nested' => (object) [
                'firstname' => 'Andreas',
                'deeper' => (object) [
                    'surname' => 'Linden',
                ],
            ],
        ];

        /* @var FirstAndLastnameObject $nestedName */
        $names = static::$serializer->denormalize($data, FirstAndLastnameObject::class);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Normalization is only supported with array-access syntax. Accessor "[nested].firstname" on class "Zolex\VOM\Test\Fixtures\FirstAndLastnameObject" uses object syntax and therefore can not be normalized.');
        static::$serializer->normalize($names);
    }

    public function testNonObjectAccessorNotFound(): void
    {
        $data = [
            'nested' => (object) [
                'firstname' => 'Andreas',
            ],
        ];

        $this->expectException(NoSuchPropertyException::class);
        static::$serializer->denormalize($data, FirstAndLastnameObject::class);
    }

    public function testAccessorListItems(): void
    {
        $data = [
            'ANOTHER_ACCESSOR' => 42,
            'THIRD' => [
                'ACCESSOR' => 'nested value',
            ],
            'ACCESSOR1' => 'something',
        ];

        $result = static::$serializer->denormalize($data, AccessorList::class);
        $this->assertCount(3, $result->genericList);
        $this->assertContainsEquals(new AccessorListGenericType('KeyForAcessor1', 'something'), $result->genericList);
        $this->assertContainsEquals(new AccessorListGenericType('AnotherKey', 42), $result->genericList);
        $this->assertContainsEquals(new AccessorListGenericType('ThridKey', 'nested value'), $result->genericList);
    }

    public function testAccessorListItemsWithWrongAccessorsThrowsException(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Model "Zolex\VOM\Test\Fixtures\AccessorListItemWithWrongAccessors" is wrapped in "Zolex\VOM\Metadata\AccessorListItemMetadata". Only valid accessors are "key", "value" and "accessor".');
        static::$serializer->denormalize(['data' => 'asd'], AccessorListWithWrongAccessors::class);
    }
}
