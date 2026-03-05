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

namespace Zolex\VOM\Test\Fixtures\Profiling;

use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class ProfilingMetric
{
    public function __construct(
        #[VOM\Argument(accessor: 'key')]
        public readonly string $key,

        #[VOM\Argument(accessor: 'value')]
        public readonly int $value,
    ) {
    }
}
