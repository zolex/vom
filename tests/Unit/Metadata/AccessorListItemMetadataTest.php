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
use Zolex\VOM\Metadata\AccessorListItemMetadata;

class AccessorListItemMetadataTest extends TestCase
{
    public function testGetters(): void
    {
        $metadata = new AccessorListItemMetadata('my_key', 'my_accessor', 'my_value');

        $this->assertEquals('my_key', $metadata->getKey());
        $this->assertEquals('my_accessor', $metadata->getAccessor());
        $this->assertEquals('my_value', $metadata->getValue());
    }

    public function testGettersWithIntKey(): void
    {
        $metadata = new AccessorListItemMetadata(0, 'accessor', ['nested' => 'data']);

        $this->assertSame(0, $metadata->getKey());
        $this->assertSame('accessor', $metadata->getAccessor());
        $this->assertEquals(['nested' => 'data'], $metadata->getValue());
    }
}
