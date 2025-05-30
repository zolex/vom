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
use Zolex\VOM\Test\Fixtures\RelativeAccessorSyntaxLevel0;
use Zolex\VOM\Test\Fixtures\RelativeAccessorSyntaxLevel1;
use Zolex\VOM\Test\Fixtures\RelativeAccessorSyntaxLevel2;
use Zolex\VOM\Test\Fixtures\RelativeAccessorSyntaxLevel3;
use Zolex\VOM\Test\Fixtures\RelativeNestingLevel0;
use Zolex\VOM\Test\Fixtures\RelativeNestingLevel1;
use Zolex\VOM\Test\Fixtures\RelativeNestingLevel2;
use Zolex\VOM\Test\Fixtures\RelativeNestingLevel3;
use Zolex\VOM\Test\Fixtures\RelativeNestingLevel4;
use Zolex\VOM\Test\Fixtures\RelativeNormalization2Level0;
use Zolex\VOM\Test\Fixtures\RelativeNormalizationLevel0;
use Zolex\VOM\Test\Fixtures\RelativeNormalizationLevel1;
use Zolex\VOM\Test\Fixtures\RelativeNormalizationLevel2;
use Zolex\VOM\Test\Fixtures\RelativeNormalizationLevel3;
use Zolex\VOM\Test\Functional\Standard\VersatileObjectMapperTestCase;

/**
 * @mixin VersatileObjectMapperTestCase
 */
class RelativeAccessorTestCase extends TestCase
{
    public function testRelativeNesting(): void
    {
        $data = [
            'LEVEL_ZERO_VALUE' => 0,
            'LEVEL_ONE' => [
                'LEVEL_ONE_VALUE' => 1,
                'LEVEL_TWO' => [
                    'LEVEL_TWO_VALUE' => 2,
                    'LEVEL_THREE' => [
                        'LEVEL_THREE_VALUE' => 3,
                        'LEVEL_FOUR' => [
                            'LEVEL_FOUR_VALUE' => 4,
                        ],
                    ],
                ],
            ],
        ];

        $relativeBrainfuck = static::$serializer->denormalize($data, RelativeNestingLevel0::class);

        $this->assertInstanceOf(RelativeNestingLevel0::class, $relativeBrainfuck);
        $this->assertEquals(0, $relativeBrainfuck->LEVEL_ZERO_VALUE);

        $this->assertInstanceOf(RelativeNestingLevel1::class, $relativeBrainfuck->LEVEL_ONE);
        $this->assertEquals(0, $relativeBrainfuck->LEVEL_ONE->LEVEL_ONE_VALUE);

        $this->assertInstanceOf(RelativeNestingLevel2::class, $relativeBrainfuck->LEVEL_ONE->LEVEL_TWO);
        $this->assertEquals(2, $relativeBrainfuck->LEVEL_ONE->LEVEL_TWO->LEVEL_TWO_VALUE);

        $this->assertInstanceOf(RelativeNestingLevel3::class, $relativeBrainfuck->LEVEL_ONE->LEVEL_TWO->LEVEL_THREE);
        $this->assertEquals(2, $relativeBrainfuck->LEVEL_ONE->LEVEL_TWO->LEVEL_THREE->LEVEL_THREE_VALUE);

        $this->assertInstanceOf(RelativeNestingLevel4::class, $relativeBrainfuck->LEVEL_ONE->LEVEL_TWO->LEVEL_THREE->LEVEL_FOUR);
        $this->assertEquals(1, $relativeBrainfuck->LEVEL_ONE->LEVEL_TWO->LEVEL_THREE->LEVEL_FOUR->LEVEL_FOUR_VALUE);
    }

    public function testRelativeAccessorSyntax(): void
    {
        $data = [
            'LEVEL_ZERO_VALUE' => 0,
            'LEVEL_ONE' => [
                'LEVEL_ONE_VALUE' => 1,
                'LEVEL_TWO' => [
                    'LEVEL_TWO_VALUE' => 2,
                    'LEVEL_THREE' => [
                        'LEVEL_THREE_VALUE' => 3,
                    ],
                ],
            ],
        ];

        $relativeBrainfuck = static::$serializer->denormalize($data, RelativeAccessorSyntaxLevel0::class);

        $this->assertInstanceOf(RelativeAccessorSyntaxLevel0::class, $relativeBrainfuck);
        $this->assertEquals(0, $relativeBrainfuck->LEVEL_ZERO_VALUE);

        $this->assertInstanceOf(RelativeAccessorSyntaxLevel1::class, $relativeBrainfuck->LEVEL_ONE);
        $this->assertEquals(0, $relativeBrainfuck->LEVEL_ONE->LEVEL_ONE_VALUE);

        $this->assertInstanceOf(RelativeAccessorSyntaxLevel2::class, $relativeBrainfuck->LEVEL_ONE->LEVEL_TWO);
        $this->assertEquals(1, $relativeBrainfuck->LEVEL_ONE->LEVEL_TWO->LEVEL_TWO_VALUE);

        $this->assertInstanceOf(RelativeAccessorSyntaxLevel3::class, $relativeBrainfuck->LEVEL_ONE->LEVEL_TWO->LEVEL_THREE);
        $this->assertEquals(0, $relativeBrainfuck->LEVEL_ONE->LEVEL_TWO->LEVEL_THREE->LEVEL_THREE_VALUE);
    }

    public function testDenormalizeRelativeAccessorWithoutNestedStructureInSourceData(): void
    {
        $data = [
            'VALUE_A' => 0,
            'VALUE_B' => 1,
            'VALUE_C' => 2,
            'VALUE_D' => 3,
        ];

        $model = static::$serializer->denormalize($data, RelativeNormalizationLevel0::class);
        $this->assertInstanceOf(RelativeNormalizationLevel0::class, $model);
        $this->assertEquals(0, $model->VALUE_A);
        $this->assertInstanceOf(RelativeNormalizationLevel1::class, $model->LEVEL_ONE);
        $this->assertEquals(1, $model->LEVEL_ONE->VALUE);
        $this->assertInstanceOf(RelativeNormalizationLevel2::class, $model->LEVEL_ONE->LEVEL_TWO);
        $this->assertEquals(2, $model->LEVEL_ONE->LEVEL_TWO->VALUE);
        $this->assertInstanceOf(RelativeNormalizationLevel3::class, $model->LEVEL_ONE->LEVEL_TWO->LEVEL_THREE);
        $this->assertEquals(3, $model->LEVEL_ONE->LEVEL_TWO->LEVEL_THREE->VALUE);
    }

    public function testNormalizeRelativeAccessor(): void
    {
        $data = [
            'VALUE_A' => 4,
            'VALUE_B' => 5,
            'VALUE_C' => 6,
            'VALUE_D' => 7,
        ];

        $model = static::$serializer->denormalize($data, RelativeNormalizationLevel0::class);
        $normalized = static::$serializer->normalize($model);
        $this->assertEquals($data, $normalized);
    }

    public function testNormalizeRelativeAccessor2(): void
    {
        $data = [
            'VALUE_A' => 8,
            'VALUE_B' => 9,
            'LEVEL_ONE' => [
                'VALUE_C' => 10,
                'VALUE_D' => 11,
            ],
        ];

        $model = static::$serializer->denormalize($data, RelativeNormalization2Level0::class);
        $normalized = static::$serializer->normalize($model);
        $this->assertEquals($data, $normalized);
    }
}
