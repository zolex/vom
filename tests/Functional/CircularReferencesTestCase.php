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
use Symfony\Component\Serializer\Exception\CircularReferenceException;
use Zolex\VOM\Test\Fixtures\CircularReference;
use Zolex\VOM\Test\Functional\Standard\VersatileObjectMapperTestCase;

/**
 * @mixin VersatileObjectMapperTestCase
 */
class CircularReferencesTestCase extends TestCase
{
    public function testCircularReferenceRethrowsException(): void
    {
        $ref1 = new CircularReference();
        $ref1->id = 1;

        $ref2 = new CircularReference();
        $ref2->id = 2;

        $ref1->reference = $ref2;
        $ref2->reference = $ref1;

        $expected = [
            'id' => 1,
            'reference' => [
                'id' => 2,
            ],
        ];

        $this->expectException(CircularReferenceException::class);
        $this->expectExceptionMessage('Consider adding "circular_reference_handler" or "skip_circular_reference" to the context.');
        $normalized = static::$serializer->normalize($ref1);
        $this->assertEquals($expected, $normalized);
    }

    public function testIngoreCircularReferenceException(): void
    {
        $ref1 = new CircularReference();
        $ref1->id = 1;

        $ref2 = new CircularReference();
        $ref2->id = 2;

        $ref1->reference = $ref2;
        $ref2->reference = $ref1;

        $expected = [
            'id' => 1,
            'reference' => [
                'id' => 2,
            ],
        ];

        $normalized = static::$serializer->normalize($ref1, null, ['skip_circular_reference' => true]);
        $this->assertEquals($expected, $normalized);
    }

    public function testCustomCircularReferenceHandler(): void
    {
        $ref1 = new CircularReference();
        $ref1->id = 1;

        $ref2 = new CircularReference();
        $ref2->id = 2;

        $ref1->reference = $ref2;
        $ref2->reference = $ref1;

        $expected = [
            'id' => 1,
            'reference' => [
                'id' => 2,
                'reference' => [
                    'id' => 1,
                    'reference' => [
                        'id' => 2,
                        'reference' => '/ref/1',
                    ],
                ],
            ],
        ];

        $normalized = static::$serializer->normalize($ref1, null, [
            'circular_reference_limit' => 2,
            'circular_reference_handler' => function ($ref) {
                return \sprintf('/ref/%d', $ref->id);
            },
        ]);
        $this->assertEquals($expected, $normalized);
    }
}
