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
use Zolex\VOM\Test\Fixtures\Person;
use Zolex\VOM\Test\Fixtures\Thing;
use Zolex\VOM\Test\Functional\Standard\VersatileObjectMapperTestCase;

/**
 * @mixin VersatileObjectMapperTestCase
 */
class ClassDiscriminatorTestCase extends TestCase
{
    public function testDenormalizePersonWithClassDiscriminator(): void
    {
        $data = [
            'type' => 'person',
            'id' => 666,
            'name' => [
                'firstname' => 'Peter',
                'lastname' => 'Enis',
            ],
        ];

        $thing = static::$serializer->denormalize($data, Thing::class, null, ['skip_null_values' => true]);
        $this->assertInstanceOf(Person::class, $thing);

        $normalized = static::$serializer->normalize($thing);
        $this->assertEquals($data, $normalized);
    }

    public function testDenormalizeAddressWithClassDiscriminator(): void
    {
        $data = [
            'type' => 'address',
            'street' => 'Examplestreet',
        ];

        $thing = static::$serializer->denormalize($data, Thing::class);
        $this->assertInstanceOf(Address::class, $thing);

        $normalized = static::$serializer->normalize($thing);
        $this->assertEquals($data, $normalized);
    }
}
