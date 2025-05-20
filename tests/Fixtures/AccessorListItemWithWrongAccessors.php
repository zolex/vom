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
class AccessorListItemWithWrongAccessors
{
    public function __construct(
        #[VOM\Argument(accessor: 'wrong.object')]
        public string|int $typeFromAccessorListKey,

        #[VOM\Argument(accessor: '[some_array]')]
        public string|int $theActualValue,
    ) {
    }
}
