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

namespace Zolex\VOM\Test\Functional\Cached;

use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Zolex\VOM\Serializer\Factory\VersatileObjectMapperFactory;
use Zolex\VOM\Serializer\VersatileObjectMapper;

/**
 * Test VOM with a cache-enabled instance.
 */
trait CachedVersatileObjectMapperTestCase
{
    protected static VersatileObjectMapper $serializer;
    protected static ParameterBag $parameterBag;

    public function __construct(?string $name = null)
    {
        parent::__construct($name);

        self::$parameterBag = new ParameterBag();
        self::$parameterBag->set('example', true);
    }

    public static function setUpBeforeClass(): void
    {
        self::$serializer = VersatileObjectMapperFactory::create(new ArrayAdapter(), [self::$parameterBag]);
    }
}
