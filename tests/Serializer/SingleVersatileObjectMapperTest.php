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

namespace Zolex\VOM\Test\Serializer;

use Zolex\VOM\Serializer\Factory\VersatileObjectMapperFactory;

/**
 * Test VOM while keeping a single instance for all tests!
 */
class SingleVersatileObjectMapperTest extends VersatileObjectMapperTest
{
    public static function setUpBeforeClass(): void
    {
        self::$serializer = VersatileObjectMapperFactory::create();
    }

    protected function setUp(): void
    {
    }
}
