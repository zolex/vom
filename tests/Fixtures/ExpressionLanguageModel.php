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
class ExpressionLanguageModel
{
    #[VOM\Property(
        accessor: false,
        denormalize: 'data["first_name"] ~ " " ~ data["last_name"]',
        normalize: 'array_combine(["first_name", "last_name"], explode(" ", object.fullName))',
    )]
    public string $fullName = '';

    #[VOM\Property(normalize: 'object.age * 2')]
    public int $doubleAge = 0;

    public int $age = 0;
}
