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
class RelativeNormalizationLevel1
{
    #[VOM\Property(accessor: '[..][VALUE_B]')]
    public int $VALUE;

    #[VOM\Property]
    public ?RelativeNormalizationLevel2 $LEVEL_TWO;
}
