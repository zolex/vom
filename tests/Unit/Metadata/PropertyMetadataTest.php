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

namespace Zolex\VOM\Test\Unit\Metadata;

use PHPUnit\Framework\TestCase;
use Zolex\VOM\Mapping\Property;
use Zolex\VOM\Metadata\PropertyMetadata;

class PropertyMetadataTest extends TestCase
{
    public function testGetters(): void
    {
        $attribute = new Property(
            '[accessor]',
            'field',
            true,
            ['foo' => 'bar'],
            'foo',
            'bar',
            'desc',
            \DateTime::W3C,
        );
        $metadata = new PropertyMetadata('name', [], $attribute);

        $this->assertEquals('name', $metadata->getName());
        $this->assertEquals('[accessor]', $metadata->getAccessor());
        $this->assertEquals('field', $metadata->getField());
        $this->asserttrue($metadata->hasAccessor());
        $this->assertTrue($metadata->isRoot());
        $this->assertEquals(['foo' => 'bar'], $metadata->getAliases());
        $this->assertEquals('bar', $metadata->getAlias('foo'));
        $this->assertNull($metadata->getAlias('not_exists'));
        $this->assertEquals('foo', $metadata->getTrueValue());
        $this->assertEquals('bar', $metadata->getFalseValue());
        $this->assertEquals('desc', $metadata->getDefaultOrder());
        $this->assertEquals(\DateTime::W3C, $metadata->getDateTimeFormat());
    }
}
