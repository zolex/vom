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
use Zolex\VOM\Mapping\Normalizer;
use Zolex\VOM\Metadata\NormalizerMetadata;

class NormalizerMetadataTest extends TestCase
{
    public function testGetMethodAndPropertyName(): void
    {
        $metadata = new NormalizerMetadata('class', 'getName', [], new Normalizer(), 'name');

        $this->assertEquals('getName', $metadata->getMethod());
        $this->assertEquals('name', $metadata->getPropertyName());
    }

    public function testGetMethodAndPropertyNameWithNonCompliantName(): void
    {
        $metadata = new NormalizerMetadata('class', 'somethingElse', [], new Normalizer(), 'somethingElse');

        $this->assertEquals('somethingElse', $metadata->getMethod());
        $this->assertEquals('somethingElse', $metadata->getPropertyName());
    }
}
