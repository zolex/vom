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
class ProfilingCustomer
{
    public function __construct(
        #[VOM\Argument('[first_name]')]
        public readonly string $firstName,

        #[VOM\Argument('[last_name]')]
        public readonly string $lastName,

        #[VOM\Argument]
        public readonly string $email,

        // Backed int enum - VOM maps the integer value to the enum case
        #[VOM\Argument]
        public readonly ProfilingTier $tier,

        // root: true — pulls orderId from the very top of the input data even though
        // this model is nested under [customer]
        #[VOM\Argument('[meta][id]', root: true)]
        public readonly int $orderId,
    ) {
    }
}
