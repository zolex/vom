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

namespace Zolex\VOM\Test\Metadata;

use PHPUnit\Framework\TestCase;
use Zolex\VOM\Mapping\Model;
use Zolex\VOM\Mapping\Property;
use Zolex\VOM\Metadata\Exception\RuntimeException;
use Zolex\VOM\Metadata\Factory\ModelMetadataFactory;
use Zolex\VOM\Metadata\ModelMetadata;
use Zolex\VOM\Metadata\PropertyMetadata;
use Zolex\VOM\PropertyInfo\Extractor\PropertyInfoExtractorFactory;
use Zolex\VOM\Test\Fixtures\SickRoot;

class ModelMetadataTest extends TestCase
{
    public function testGetters(): void
    {
        $model = new Model();

        $metadata = new ModelMetadata('class');
        $metadata->setAttribute($model);
        $prop = new PropertyMetadata('name', [], new Property());
        $metadata->addProperty($prop);
        $properties = $metadata->getProperties();

        $this->assertSame($model, $metadata->getAttribute());
        $this->assertCount(1, $properties);
        $this->assertArrayHasKey('name', $properties);
        $this->assertSame($prop, $properties['name']);
        $this->assertSame($prop, $metadata->getProperty('name'));
    }

    public function testGetNestedMetadata(): void
    {
        $factory = new ModelMetadataFactory(PropertyInfoExtractorFactory::create());
        $metadata = $factory->getMetadataFor(SickRoot::class);

        $firstname = $metadata->find('singleChild.firstname', $factory);
        $this->assertInstanceOf(PropertyMetadata::class, $firstname);

        $sickedy = $metadata->find('sickSack.sickSuck.sickedy', $factory);
        $this->assertInstanceOf(PropertyMetadata::class, $sickedy);

        $this->expectException(RuntimeException::class);
        $metadata->find('singleChild.non.existent', $factory);
    }
}
