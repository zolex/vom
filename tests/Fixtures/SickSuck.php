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
class SickSuck
{
    #[VOM\Property('[SICK_FROM_ROOT]')]
    public string $sickedy;

    #[VOM\Property('[SACK_FROM_ROOT]')]
    public string $sackedy;
}
