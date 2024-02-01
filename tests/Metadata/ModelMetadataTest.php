<?php

namespace Zolex\VOM\Test\Metadata;

use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Annotation\Groups;
use Zolex\VOM\Mapping\Model;
use Zolex\VOM\Mapping\Property;
use Zolex\VOM\Metadata\Factory\ModelMetadataFactory;
use Zolex\VOM\Metadata\Factory\PropertyMetadataFactory;
use Zolex\VOM\Metadata\ModelMetadata;
use Zolex\VOM\Metadata\PropertyMetadata;
use Zolex\VOM\Symfony\PropertyInfo\PropertyInfoExtractorFactory;
use Zolex\VOM\Test\Fixtures\SickRoot;

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
        $types = [new Type('string')];
        $prop = new PropertyMetadata('name', $types, new Property(), ['group']);
        $metadata->addProperty($prop);
        $properties = $metadata->getProperties();

        $this->assertSame($model, $metadata->getAttribute());
        $this->assertCount(1, $properties);
        $this->assertArrayHasKey('name', $properties);
        $this->assertSame($prop, $properties['name']);
        $this->assertSame($prop, $metadata->getProperty('name'));
    }

    public function testGetNestedMetadataWithPropertyAccessor(): void
    {
        $factory = new ModelMetadataFactory(PropertyInfoExtractorFactory::create());
        $metadata = $factory->create(SickRoot::class);
        $accessor = PropertyAccess::createPropertyAccessor();

        $firstname = $accessor->getValue($metadata, 'singleChild.firstname');
        $this->assertInstanceOf(PropertyMetadata::class, $firstname);

        $sickedy = $accessor->getValue($metadata, 'sickSack.sickSuck.sickedy');
        $this->assertInstanceOf(PropertyMetadata::class, $sickedy);
    }
}
