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
use Zolex\VOM\Test\Fixtures\Address;
use Zolex\VOM\Test\Fixtures\Arrays;
use Zolex\VOM\Test\Fixtures\NestingRoot;
use Zolex\VOM\Test\Fixtures\Person;
use Zolex\VOM\Test\Functional\Standard\VersatileObjectMapperTestCase;

/**
 * @mixin VersatileObjectMapperTestCase
 */
class NestingTestCase extends TestCase
{
    public function testRecursiveStructures(): void
    {
        $data = [
            'dateTimeList' => [
                ['dateTime' => '2024-01-01 00:00:00'],
            ],
            'recursiveList' => [
                [
                    'dateTimeList' => [
                        ['dateTime' => '2024-01-02 00:00:00'],
                        ['dateTime' => '2024-01-03 00:00:00'],
                    ],
                    'recursiveList' => [
                        [
                            'dateTimeList' => [
                                ['dateTime' => '2024-01-03 00:00:00'],
                                ['dateTime' => '2024-01-04 00:00:00'],
                                ['dateTime' => '2024-01-05 00:00:00'],
                            ],
                            'recursiveList' => [
                                [
                                    'dateTimeList' => [
                                        ['dateTime' => '2024-01-06 00:00:00'],
                                        ['dateTime' => '2024-01-07 00:00:00'],
                                        ['dateTime' => '2024-01-08 00:00:00'],
                                        ['dateTime' => '2024-01-09 00:00:00'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'dateTimeList' => [
                        ['dateTime' => '2024-01-10 00:00:00'],
                    ],
                    'recursiveList' => [
                        [
                            'dateTimeList' => [
                                ['dateTime' => '2024-01-11 00:00:00'],
                                ['dateTime' => '2024-01-12 00:00:00'],
                            ],
                            'recursiveList' => [
                                [
                                    'dateTimeList' => [
                                        ['dateTime' => '2024-01-13 00:00:00'],
                                        ['dateTime' => '2024-01-14 00:00:00'],
                                        ['dateTime' => '2024-01-15 00:00:00'],
                                    ],
                                    'recursiveList' => [
                                        [
                                            'dateTimeList' => [
                                                ['dateTime' => '2024-01-16 00:00:00'],
                                                ['dateTime' => '2024-01-17 00:00:00'],
                                                ['dateTime' => '2024-01-18 00:00:00'],
                                                ['dateTime' => '2024-01-19 00:00:00'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $items = static::$serializer->denormalize($data, Arrays::class);
        $data2 = static::$serializer->normalize($items);

        $this->assertEquals($data, $data2);
    }

    /**
     * @dataProvider createNestedModelsDataProvider
     */
    public function testCreateNestedModels(array $data, string $className, string|array $groups, object $expectedModel)
    {
        $model = static::$serializer->denormalize($data, $className, null, ['groups' => $groups]);
        $this->assertEquals($expectedModel, $model);
    }

    public static function createNestedModelsDataProvider(): iterable
    {
        yield [
            [
                'id' => 42,
                'name' => [
                    'firstname' => 'The',
                    'lastname' => 'Dude',
                ],
            ],
            Person::class,
            ['id'],
            Person::create(id: 42),
        ];

        yield [
            [
                'id' => 42,
                'name' => [
                    'firstname' => 'The',
                    'lastname' => 'Dude',
                ],
                'int_age' => 38,
                'contact_email' => 'some@mail.to',
                'address' => [
                    'street' => 'nowhere',
                ],
            ],
            Person::class,
            ['standard'],
            Person::create(id: 42, firstname: 'The', lastname: 'Dude', age: 38, email: 'some@mail.to'),
        ];

        yield [
            [
                'id' => 42,
                'int_age' => 42,
                'contact_email' => 'some@mail.to',
                'address' => [
                    'street' => 'Fireroad',
                    'housenumber' => '666',
                    'zipcode' => '56070',
                    'city' => 'Hell',
                ],
                'bool_awesome' => 'y',
                'hilarious' => 'OFF',
                'flags' => [
                    'delicious' => 'delicious',
                    'holy' => 'holy',
                ],
            ],
            Person::class,
            ['extended'],
            Person::create(
                id: 42,
                age: 42,
                email: 'some@mail.to',
                isAwesome: true,
                isHilarious: false,
                address: Address::create(
                    street: 'Fireroad',
                    houseNo: '666',
                    zip: '56070',
                    city: 'Hell',
                ),
            ),
        ];

        yield [
            [
                'id' => 43,
                'int_age' => 42,
                'contact_email' => 'some@mail.to',
                'address' => [
                    'street' => 'Fireroad',
                    'housenumber' => '32',
                    'zipcode' => '50210',
                    'city' => 'Hell',
                ],
                'bool_awesome' => 'true',
                'hilarious' => 'ON',
                'flags' => [
                    'delicious' => 'delicious',
                ],
            ],
            Person::class,
            ['id', 'isHoly', 'isHilarious'],
            Person::create(
                id: 43,
                isHilarious: true,
            ),
        ];

        yield [
            [
                'id' => 44,
                'hilarious' => 'ON',
                'address' => [
                    'street' => 'Fireroad',
                    'housenumber' => '213456',
                    'zipcode' => '98765',
                    'city' => 'Dunkel',
                ],
            ],
            Person::class,
            ['id', 'address'],
            Person::create(
                id: 44,
                address: Address::create(
                    street: 'Fireroad',
                    houseNo: '213456',
                    zip: '98765',
                    city: 'Dunkel',
                ),
            ),
        ];

        yield [
            [
                'id' => 45,
                'address' => [
                    'street' => 'Elmstreet',
                    'housenumber' => '666',
                ],
            ],
            Person::class,
            ['address'],
            Person::create(
                address: Address::create(
                    street: 'Elmstreet',
                    houseNo: '666',
                ),
            ),
        ];
    }

    public function testRootWithNestedAndAccessors(): void
    {
        $data = [
            'ROOT' => [
                'VALUE' => '0',
            ],
            'LEVEL_ONE' => [
                'LEVEL_ONE_VALUE' => '1',
            ],
            'LEVEL_TWO_VALUE' => '2',
            'LEVEL_THREE' => [
                'LEVEL_FOUR' => [
                    'LEVEL_FOUR_VALUE' => '4',
                ],
            ],
            'NESTED' => [
                'LEVEL_THREE_VALUE' => '3',
            ],
        ];

        $nestingRoot = static::$serializer->denormalize($data, NestingRoot::class);
        $normalized = static::$serializer->normalize($nestingRoot);

        $this->assertEquals($data, $normalized);
    }
}
