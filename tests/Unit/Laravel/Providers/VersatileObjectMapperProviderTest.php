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

namespace Zolex\VOM\Test\Unit\Laravel\Providers;

use PHPUnit\Framework\TestCase;
use Zolex\VOM\Laravel\Providers\VersatileObjectMapperProvider;
use Zolex\VOM\Serializer\VersatileObjectMapper;
use Zolex\VOM\Test\Unit\Laravel\Illuminate\Contracts\Foundation\DummyApplication;

class VersatileObjectMapperProviderTest extends TestCase
{
    public function testSingletonIsRegistered(): void
    {
        $app = new DummyApplication();

        $provider = new VersatileObjectMapperProvider();
        $provider->app = $app;
        $provider->register();

        $this->assertInstanceOf(VersatileObjectMapper::class, $app(VersatileObjectMapper::class));
    }
}
