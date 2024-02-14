<?php

/*
 * This file is part of the VOM package.
 *
 * (c) Andreas Linden <zlx@gmx.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fixtures;

use Zolex\VOM\Test\Fixtures\FirstAndLastname;

class NestedNameArray
{
    /** @var array|FirstAndLastname[] */
    private array $nestedNames;
}
