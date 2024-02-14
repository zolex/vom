<?php

/*
 * This file is part of the VOM package.
 *
 * (c) Andreas Linden <zlx@gmx.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zolex\VOM\Test\Serializer;

use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Zolex\VOM\Serializer\Factory\VersatileObjectMapperFactory;

/**
 * Test VOM with a fresh cache-enabled instance for each test.
 */
class CachedVersatileObjectMapperTest extends VersatileObjectMapperTest
{
    protected function setUp(): void
    {
        self::$serializer = VersatileObjectMapperFactory::create(new ArrayAdapter());
    }
}
