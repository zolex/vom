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

namespace Zolex\VOM\Test\Fixtures;

use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class RelativeNestingLevel2
{
    #[VOM\Property]
    public int $LEVEL_TWO_VALUE;

    #[VOM\Property(accessor: '[LEVEL_TWO]', relative: 1)]
    public RelativeNestingLevel3 $LEVEL_THREE;
}
