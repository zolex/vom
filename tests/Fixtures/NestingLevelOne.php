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

use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class NestingLevelOne
{
    #[VOM\Property('LEVEL_ONE_VALUE')]
    public string $value;

    #[VOM\Property(accessor: false, root: true)]
    public NestingLevelTwo $levelTwo;
}
