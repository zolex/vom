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

namespace Zolex\VOM\Test\Unit\Laravel\Illuminate\Contracts\Foundation;

class DummyApplication
{
    private array $singletons = [];

    public function __invoke($class): ?object
    {
        if (isset($this->singletons[$class])) {
            return $this->singletons[$class];
        }

        return null;
    }

    public function singleton(string $class, callable $closure): object
    {
        if (!isset($this->singletons[$class])) {
            $this->singletons[$class] = \call_user_func_array($closure, [$this]);
        }

        return $this->singletons[$class];
    }
}
