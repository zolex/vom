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

// Flattened onto the parent's data level via accessor: false on the parent property.
// All properties here are resolved against the same data as the parent (root).
#[VOM\Model]
class ProfilingAddress
{
    #[VOM\Property]
    public string $street;

    #[VOM\Property]
    public string $city;

    #[VOM\Property]
    public string $country;

    // Deep path from the same (root) data level even though this model is flattened
    #[VOM\Property('[address][zip]')]
    public string $zipCode;
}
