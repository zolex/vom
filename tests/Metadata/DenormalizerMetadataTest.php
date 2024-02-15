<?php

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
use Zolex\VOM\Mapping\Property;
use Zolex\VOM\Metadata\DenormalizerMetadata;
use Zolex\VOM\Metadata\PropertyMetadata;

class DenormalizerMetadataTest extends TestCase
{
    public function testGetGetMethodAndAttribute(): void
    {
        $args = [
            new PropertyMetadata('id', [], new Property()),
            new PropertyMetadata('name', [], new Property()),
        ];

        $metadata = new DenormalizerMetadata(['class', 'getData'], $args, 'data');

        $this->assertEquals('getData', $metadata->getMethod());
        $this->assertEquals('data', $metadata->getPropertyName());
        $this->assertEquals($args, $metadata->getArguments());
    }

    public function testGetGetMethodAndAttributeWithNonCompliantName(): void
    {
        $args = [
            new PropertyMetadata('id', [], new Property()),
            new PropertyMetadata('name', [], new Property()),
        ];

        $metadata = new DenormalizerMetadata(['class', 'somethingElse'], $args, 'somethingElse');

        $this->assertEquals('somethingElse', $metadata->getMethod());
        $this->assertEquals('somethingElse', $metadata->getPropertyName());
        $this->assertEquals($args, $metadata->getArguments());
    }
}
