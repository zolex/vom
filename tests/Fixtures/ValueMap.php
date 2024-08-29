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
class ValueMap
{
    #[VOM\Property(map: [
        'TYPE1' => 'A',
        'TYPE2' => 'B',
        'TYPE3' => 'C',
    ])]
    public string $type;

    #[VOM\Property(map: [
        'RED' => '#FF0000',
        'GREEN' => '#00FF00',
        'BLUE' => '#0000FF',
    ])]
    public string $color = '#000000';
}
