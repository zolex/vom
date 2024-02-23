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

namespace Zolex\VOM\Laravel\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Zolex\VOM\Serializer\Factory\VersatileObjectMapperFactory;
use Zolex\VOM\Serializer\VersatileObjectMapper;

if (!class_exists('Illuminate\Support\ServiceProvider')) {
    try {
        class_alias(\stdClass::class, 'Illuminate\Support\ServiceProvider');
    } catch (\Throwable) {
    }
}

if (!class_exists('Illuminate\Contracts\Foundation\Application')) {
    try {
        class_alias(\stdClass::class, 'Illuminate\Contracts\Foundation\Application');
    } catch (\Throwable) {
    }
}

/**
 * @codeCoverageIgnore
 */
class VersatileObjectMapperProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(VersatileObjectMapper::class, function (Application $app) {
            return VersatileObjectMapperFactory::create();
        });
    }

    public function boot(): void
    {
    }
}
