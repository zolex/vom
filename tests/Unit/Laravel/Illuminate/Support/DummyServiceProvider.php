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

namespace Zolex\VOM\Test\Unit\Laravel\Illuminate\Support;

use Illuminate\Contracts\Foundation\Application;

class DummyServiceProvider
{
    public Application $app;
}
