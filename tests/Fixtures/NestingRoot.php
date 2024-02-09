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

use Symfony\Component\Serializer\Attribute\Context;
use Zolex\VOM\Mapping as VOM;

#[Context(['allow_object_syntax' => true])]
#[VOM\Model]
class NestingRoot
{
    #[VOM\Property('ROOT.VALUE')]
    public string $value;

    #[VOM\Property('LEVEL_ONE')]
    public NestingLevelOne $levelOne;
}
