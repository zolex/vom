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
use Zolex\VOM\Metadata\Exception\MappingException;
use Zolex\VOM\Test\Fixtures\CollectionOfCollections;
use Zolex\VOM\Test\Fixtures\CollectionPublic;
use Zolex\VOM\Test\Fixtures\CollectionWithAdderRemover;
use Zolex\VOM\Test\Fixtures\CollectionWithMutator;
use Zolex\VOM\Test\Fixtures\DateAndTime;
use Zolex\VOM\Test\Fixtures\Doctrine\DoctrinePerson;
use Zolex\VOM\Test\Fixtures\FirstAndLastname;
use Zolex\VOM\Test\Functional\Standard\VersatileObjectMapperTestCase;

/**
 * @mixin VersatileObjectMapperTestCase
 */
class CollectionsTestCase extends TestCase
{
    use VersatileObjectMapperTestCase;

    public function testArrayOfModels(): void
    {
        $data = [
            [
                'nested' => [
                    'firstname' => 'Andreas',
                    'deeper' => [
                        'surname' => 'Linden',
                    ],
                ],
            ],
            [
                'nested' => [
                    'firstname' => 'Javier',
                    'deeper' => [
                        'surname' => 'Caballero',
                    ],
                ],
            ],
            [
                'nested' => [
                    'firstname' => 'Peter',
                    'deeper' => [
                        'surname' => 'Enis',
                    ],
                ],
            ],
        ];

        /* @var array|FirstAndLastname[] $nestedName */
        $nestedNames = static::$serializer->denormalize($data, FirstAndLastname::class.'[]');

        $this->assertIsArray($nestedNames);
        $this->assertCount(3, $nestedNames);
        foreach ($data as $index => $item) {
            $this->assertEquals($item['nested']['firstname'], $nestedNames[$index]->firstname);
            $this->assertEquals($item['nested']['deeper']['surname'], $nestedNames[$index]->lastname);
        }
    }

    public function testArrayOfArrays(): void
    {
        $data = [
            'array' => [
                [1, 2, 3],
                [4, 5, 6],
            ],
            'collection' => [
                [
                    ['dateTime' => '2011-05-15 10:11:12'],
                    ['dateTime' => '2012-06-16 10:11:12'],
                ],
                [
                    ['dateTime' => '2013-07-15 10:11:12'],
                    ['dateTime' => '2014-08-15 10:11:12'],
                ],
            ],
        ];

        $collection = static::$serializer->denormalize($data, CollectionOfCollections::class);
        $normalized = static::$serializer->normalize($collection);
        $this->assertEquals($data, $normalized);
    }

    public function testArrayOnRoot(): void
    {
        $data = [
            ['dateTime' => '2024-01-01 00:00:00'],
            ['dateTime' => '2024-01-02 00:00:00'],
            ['dateTime' => '2024-01-03 00:00:00'],
        ];

        /** @var DateAndTime[] $arrayOfDateAndTime */
        $arrayOfDateAndTime = static::$serializer->denormalize($data, DateAndTime::class.'[]');
        $this->assertCount(3, $arrayOfDateAndTime);
        $this->assertEquals('2024-01-01 00:00:00', $arrayOfDateAndTime[0]->dateTime->format('Y-m-d H:i:s'));
        $this->assertEquals('2024-01-02 00:00:00', $arrayOfDateAndTime[1]->dateTime->format('Y-m-d H:i:s'));
        $this->assertEquals('2024-01-03 00:00:00', $arrayOfDateAndTime[2]->dateTime->format('Y-m-d H:i:s'));
    }

    public function testPublicCollectionThrowsException(): void
    {
        $data = [
            'people' => [
                [
                    'type' => 'person',
                    'id' => 1,
                    'name' => [
                        'firstname' => 'Andreas',
                    ],
                    'address' => [
                        'type' => 'address',
                    ],
                ],
            ],
        ];

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('The property "Zolex\VOM\Test\Fixtures\CollectionPublic::$people" seems to implement ArrayAccess. To allow VOM denormalizing it, create adder/remover methods or a mutator method accepting an array.');
        static::$serializer->denormalize($data, CollectionPublic::class);
    }

    public function testArrayAccessCollectionWithMutator(): void
    {
        $data = [
            'people' => [
                [
                    'type' => 'person',
                    'id' => 1,
                    'name' => [
                        'firstname' => 'Andreas',
                        'lastname' => 'Linden',
                    ],
                    'address' => [
                        'type' => 'address',
                        'street' => 'Elmstreet',
                        'housenumber' => '123',
                    ],
                ], [
                    'type' => 'person',
                    'id' => 2,
                    'name' => [
                        'firstname' => 'Peter',
                        'lastname' => 'Enis',
                    ],
                    'address' => [
                        'type' => 'address',
                        'zipcode' => '54321',
                        'country' => 'DE',
                    ],
                ],
            ],
        ];

        $instantiableNestedCollection = static::$serializer->denormalize($data, CollectionWithMutator::class);
        $this->assertInstanceOf(CollectionWithMutator::class, $instantiableNestedCollection);
        $this->assertInstanceOf(\ArrayObject::class, $instantiableNestedCollection->getPeople());
        $this->assertCount(2, $instantiableNestedCollection->getPeople());

        $normalized = static::$serializer->normalize($instantiableNestedCollection);
        $this->assertEquals($data, $normalized);
    }

    public function testArrayAccessCollectionWithAdderRemover(): void
    {
        $data = [
            'people' => [
                [
                    'type' => 'person',
                    'id' => 1,
                    'name' => [
                        'firstname' => 'Andreas',
                        'lastname' => 'Linden',
                    ],
                    'address' => [
                        'type' => 'address',
                        'street' => 'Elmstreet',
                        'housenumber' => '123',
                    ],
                ], [
                    'type' => 'person',
                    'id' => 2,
                    'name' => [
                        'firstname' => 'Peter',
                        'lastname' => 'Enis',
                    ],
                    'address' => [
                        'type' => 'address',
                        'zipcode' => '54321',
                        'country' => 'DE',
                    ],
                ],
            ],
        ];

        $instantiableNestedCollection = static::$serializer->denormalize($data, CollectionWithAdderRemover::class);
        $this->assertInstanceOf(CollectionWithAdderRemover::class, $instantiableNestedCollection);
        $this->assertInstanceOf(\ArrayObject::class, $instantiableNestedCollection->getPeople());
        $this->assertCount(2, $instantiableNestedCollection->getPeople());

        $normalized = static::$serializer->normalize($instantiableNestedCollection);
        $this->assertEquals($data, $normalized);
    }

    public function testDoctrineCollection(): void
    {
        $data = [
            'name' => 'Peter Parker',
            'addresses' => [
                [
                    'street' => 'Examplestreet',
                ],
            ],
        ];

        $person = static::$serializer->denormalize($data, DoctrinePerson::class);
        $normalized = static::$serializer->normalize($person);
        $this->assertArrayHasKey('name', $normalized);
        $this->assertEquals($data['name'], $normalized['name']);
        $this->assertArrayHasKey('addresses', $normalized);
        $this->assertEquals($data['addresses'], $normalized['addresses']);
    }
}
