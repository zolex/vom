<?php

namespace Zolex\VOM\Test\Metadata;

use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Type;
use Zolex\VOM\Mapping\Property;
use Zolex\VOM\Metadata\PropertyMetadata;

class PropertyMetadataTest extends TestCase
{
    public function testGetters(): void
    {
        $attribute = new Property(
            'accessor',
            'field',
            false,
            true,
            ['foo' => 'bar'],
            true,
            'foo',
            'bar',
            'desc',
            \DateTime::W3C,
        );
        $types = [new Type('int')];
        $metadata = new PropertyMetadata('name', $types, $attribute);

        $this->assertEquals('name', $metadata->getName());
        $this->assertNull($metadata->getType());
        $this->assertEquals('accessor', $metadata->getAccessor());
        $this->assertEquals('field', $metadata->getField());
        $this->assertFalse($metadata->isNested());
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
