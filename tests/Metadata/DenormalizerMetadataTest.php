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
            new PropertyMetadata('id', 'int', null, new Property()),
            new PropertyMetadata('name', 'string', null, new Property()),
        ];

        $metadata = new DenormalizerMetadata('getData', $args);

        $this->assertEquals('getData', $metadata->getMethod());
        $this->assertEquals('data', $metadata->getAttribute());
        $this->assertEquals($args, $metadata->getArguments());
    }

    public function testGetGetMethodAndAttributeWithNonCompliantName(): void
    {
        $args = [
            new PropertyMetadata('id', 'int', null, new Property()),
            new PropertyMetadata('name', 'string', null, new Property()),
        ];

        $metadata = new DenormalizerMetadata('somethingElse', $args);

        $this->assertEquals('somethingElse', $metadata->getMethod());
        $this->assertEquals('somethingElse', $metadata->getAttribute());
        $this->assertEquals($args, $metadata->getArguments());
    }
}
