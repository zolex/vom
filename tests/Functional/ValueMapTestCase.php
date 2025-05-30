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
use Zolex\VOM\Test\Fixtures\ValueMap;
use Zolex\VOM\Test\Functional\Standard\VersatileObjectMapperTestCase;

/**
 * @mixin VersatileObjectMapperTestCase
 */
class ValueMapTestCase extends TestCase
{
    public function testValueMapping(): void
    {
        $model = static::$serializer->denormalize([
            'type' => 'TYPE1',
            'color' => 'RED',
        ], ValueMap::class);

        $this->assertEquals('A', $model->type);
        $this->assertEquals('#FF0000', $model->color);
    }

    public function testValueMappingWithInvalidValueAndNoDefaultValue(): void
    {
        $model = static::$serializer->denormalize([
            'type' => 'WRONG_TYPE',
        ], ValueMap::class);

        $this->assertFalse(isset($model->type));
    }

    public function testValueMappingWithInvalidValueAndExistingDefaultValue(): void
    {
        $model = static::$serializer->denormalize([
            'color' => 'RAINBOW',
        ], ValueMap::class);

        $this->assertEquals('#000000', $model->color);
    }

    public function testValueMappingWithNullValue(): void
    {
        $model = static::$serializer->denormalize([
            'nullable' => 'NULL',
        ], ValueMap::class);

        $this->assertNull($model->nullable);
    }
}
