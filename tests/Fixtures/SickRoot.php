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
class SickRoot
{
    public int $number;

    #[VOM\Property('[SINGLE]')]
    public SickChild $singleChild;

    #[VOM\Property('[ANOTHER]')]
    public SickChild $anotherChild;

    #[VOM\Property]
    public SickSack $sickSack;

    /**
     * @var SickChild[]
     */
    #[VOM\Property()]
    public array $tooManyChildren;
}
