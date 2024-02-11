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

use Symfony\Component\Serializer\Attribute\DiscriminatorMap;
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
#[DiscriminatorMap(typeProperty: 'type', mapping: [
    'person' => Person::class,
    'address' => Address::class,
])]
abstract class Thing
{
    #[VOM\Property]
    private string $type;

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
