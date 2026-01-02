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

namespace Zolex\VOM\Metadata;

use Symfony\Component\TypeInfo\Type;
use Zolex\VOM\Mapping\Argument;

class ArgumentMetadata extends PropertyMetadata
{
    public function __construct(
        string $name,
        Type $type,
        Argument $attribute,
    ) {
        parent::__construct($name, $type, $attribute);
    }
}
