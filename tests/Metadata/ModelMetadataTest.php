<?php

namespace Zolex\VOM\Test\Metadata;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Annotation\Groups;
use Zolex\VOM\Mapping\Model;
use Zolex\VOM\Mapping\Property;
use Zolex\VOM\Metadata\ModelMetadata;
use Zolex\VOM\Metadata\PropertyMetadata;

class ModelMetadataTest extends TestCase
{
    public function testGetters(): void
    {
        $model = new Model(
            presets: ['preset_a' => ['foo'], 'preset_b' => ['bar']],
            searchable: ['searchable'],
        );

        $metadata = new ModelMetadata();
        $metadata->setAttribute($model);
        $prop = new PropertyMetadata('name', 'type', new Property(), new Groups(['group']), null);
        $metadata->addProperty($prop);
        $properties = $metadata->getProperties();

        $this->assertSame($model, $metadata->getAttribute());
        $this->assertCount(1, $properties);
        $this->assertArrayHasKey('name', $properties);
        $this->assertSame($prop, $properties['name']);
        $this->assertSame($prop, $metadata->getProperty('name'));
    }
}
