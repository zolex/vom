<?php

/*
 * This file is part of the VOM package.
 *
 * (c) Andreas Linden <zlx@gmx.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zolex\VOM\Metadata;

use Symfony\Component\PropertyInfo\Type;
use Zolex\VOM\Mapping\Argument;

class ArgumentMetadata extends PropertyMetadata
{
    public function __construct(
        string $name,
        /* @var array|Type[] $types */
        array $types,
        Argument $attribute,
        private readonly bool $isPromoted = false,
    ) {
        parent::__construct($name, $types, $attribute);
    }

    public function isPromoted(): bool
    {
        return $this->isPromoted;
    }
}
