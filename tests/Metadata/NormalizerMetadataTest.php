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
use Zolex\VOM\Metadata\NormalizerMetadata;

class NormalizerMetadataTest extends TestCase
{
    public function testGetGetMethodAndAttribute(): void
    {
        $metadata = new NormalizerMetadata('getName');

        $this->assertEquals('getName', $metadata->getMethod());
        $this->assertEquals('name', $metadata->getAttribute());
    }

    public function testGetGetMethodAndAttributeWithNonCompliantName(): void
    {
        $metadata = new NormalizerMetadata('somethingElse');

        $this->assertEquals('somethingElse', $metadata->getMethod());
        $this->assertEquals('somethingElse', $metadata->getAttribute());
    }
}
