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

namespace Zolex\VOM\Test\Laravel\Providers;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Zolex\VOM\Laravel\Providers\VersatileObjectMapperProvider;
use Zolex\VOM\Serializer\VersatileObjectMapper;
use Zolex\VOM\Test\Laravel\Illuminate\Contracts\Foundation\DummyApplication;

class VersatileObjectMapperProviderTest extends TestCase
{
    use ProphecyTrait;

    public function testSingletonIsRegistered(): void
    {
        $app = $this->prophesize(DummyApplication::class);
        $app->singleton(Argument::is(VersatileObjectMapper::class), Argument::type('callable'))
            ->shouldBeCalledOnce();

        $provider = new VersatileObjectMapperProvider();
        $provider->app = $app->reveal();
        $provider->register();

        $vom = $provider->create($provider->app);
        $this->assertInstanceOf(VersatileObjectMapper::class, $vom);
    }
}
