<?php

/*
 * This file is part of the VOM package.
 *
 * (c) Andreas Linden <zlx@gmx.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zolex\VOM\Test\Fixtures;

trait GenericFactoryTrait
{
    public static function create(...$args): static
    {
        $self = new static();
        foreach ($args as $name => $val) {
            if (property_exists($self, $name)) {
                $self->{$name} = $val;
            }
        }

        return $self;
    }
}
