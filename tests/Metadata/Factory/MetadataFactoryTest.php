<?php

/*
 * This file is part of the VOM package.
 *
 * (c) Andreas Linden <zlx@gmx.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zolex\VOM\Test\Metadata\Factory;

use PHPUnit\Framework\TestCase;
use Zolex\VOM\Metadata\Factory\Exception\RuntimeException;
use Zolex\VOM\Metadata\Factory\ModelMetadataFactory;
use Zolex\VOM\Metadata\ModelMetadata;
use Zolex\VOM\PropertyInfo\Extractor\PropertyInfoExtractorFactory;
use Zolex\VOM\Test\Fixtures\Instantiable;
use Zolex\VOM\Test\Fixtures\InstantiableNestedCollection;
use Zolex\VOM\Test\Fixtures\InstantiableWithDocTag;
use Zolex\VOM\Test\Fixtures\NonInstantiable;

class MetadataFactoryTest extends TestCase
{
    public function testInstantiableNestedObject(): void
    {
        $factory = new ModelMetadataFactory(PropertyInfoExtractorFactory::create());

        $metadata = $factory->getMetadataFor(Instantiable::class);
        $this->assertInstanceOf(ModelMetadata::class, $metadata);
    }

    public function testNonInstantiableNestedObject(): void
    {
        $factory = new ModelMetadataFactory(PropertyInfoExtractorFactory::create());

        $this->expectException(RuntimeException::class);
        $factory->getMetadataFor(NonInstantiable::class);
    }

    public function testInstantiableNestedObjectWithPhpDoc(): void
    {
        $factory = new ModelMetadataFactory(PropertyInfoExtractorFactory::create());

        $metadata = $factory->getMetadataFor(InstantiableWithDocTag::class);
        $this->assertInstanceOf(ModelMetadata::class, $metadata);
    }

    public function testInstantiableNestedCollection(): void
    {
        $factory = new ModelMetadataFactory(PropertyInfoExtractorFactory::create());

        $metadata = $factory->getMetadataFor(InstantiableNestedCollection::class);
        $this->assertInstanceOf(ModelMetadata::class, $metadata);
    }
}
