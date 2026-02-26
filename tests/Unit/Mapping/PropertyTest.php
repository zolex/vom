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

namespace Zolex\VOM\Test\Unit\Mapping;

use PHPUnit\Framework\TestCase;
use Zolex\VOM\Mapping\Property;

class PropertyTest extends TestCase
{
    public function testArrayAccessorWithNonStringElement(): void
    {
        // Non-string elements (integers) in an array accessor are skipped
        // during relative accessor processing, exercising the `continue` branch.
        $prop = new Property(accessor: ['path', 42, 'other']);
        $accessor = $prop->getAccessor();
        $this->assertIsArray($accessor);
        $this->assertSame('path', $accessor[0]);
        $this->assertSame(42, $accessor[1]);
        $this->assertSame('other', $accessor[2]);
    }

    public function testGetters(): void
    {
        $prop = new Property(
            accessor: 'foo',
            field: 'bar',
            root: true,
            aliases: ['foo' => 'bar'],
            trueValue: 'foo',
            falseValue: 'bar',
            defaultOrder: 'DESC',
            dateTimeFormat: \DateTime::W3C,
        );

        $this->assertEquals('foo', $prop->getAccessor());
        $this->assertEquals('bar', $prop->getField());
        $this->assertTrue($prop->hasAccessor());
        $this->assertTrue($prop->isRoot());
        $this->assertEquals(['foo' => 'bar'], $prop->getAliases());
        $this->assertEquals('foo', $prop->getTrueValue());
        $this->assertEquals('bar', $prop->getFalseValue());
        $this->assertEquals('DESC', $prop->getDefaultOrder());
        $this->assertEquals(\DateTime::W3C, $prop->getDateTimeFormat());
    }
}
