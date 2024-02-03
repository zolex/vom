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
class CommonFlags
{
    #[VOM\Property(flag: true)]
    public bool $flagA;

    #[VOM\Property(flag: true)]
    public bool $flagB;

    #[VOM\Property(flag: true)]
    public ?bool $flagC = null;

    #[VOM\Property(flag: true)]
    public bool $flagD = true;
}
