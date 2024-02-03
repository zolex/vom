<?php

/*
 * This file is part of the VOM package.
 *
 * (c) Andreas Linden <zlx@gmx.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace VersatileObjectMapper;

use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Zolex\VOM\Serializer\Factory\VersatileObjectMapperFactory;
use Zolex\VOM\Test\VersatileObjectMapper\VersatileObjectMapperTest;

class SingleCachedVersatileObjectMapperTest extends VersatileObjectMapperTest
{
    public static function setUpBeforeClass(): void
    {
        // dont setup
    }

    protected function setUp(): void
    {
        self::$serializer = VersatileObjectMapperFactory::create(new ArrayAdapter());
    }
}
