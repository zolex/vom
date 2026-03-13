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
class ConditionalMapping
{
    #[VOM\Property('[SOURCE_PARAM_A]', if: 'data["SOURCE_CASE"] == "CASE_A"')]
    #[VOM\Property('[SOURCE_PARAM_B]', if: 'data["SOURCE_CASE"] == "CASE_B"')]
    public string $paramOne;

    #[VOM\Property(denormalize: 'data["SOURCE_CASE"] == "CASE_A" ? data["SOURCE_PARAM_A"] : data["SOURCE_PARAM_B"]')]
    public string $paramTwo;

    #[VOM\Property('[SOURCE_PARAM_A]', if: [self::class, 'isCaseA'])]
    #[VOM\Property('[SOURCE_PARAM_B]', if: [self::class, 'isCaseB'])]
    public string $paramThree;

    public static function isCaseA(array $data): bool
    {
        return 'CASE_A' === $data['SOURCE_CASE'];
    }

    public static function isCaseB(array $data): bool
    {
        return 'CASE_B' === $data['SOURCE_CASE'];
    }
}
