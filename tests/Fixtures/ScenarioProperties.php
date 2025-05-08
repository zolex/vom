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

use Zolex\VOM\Mapping\Model;
use Zolex\VOM\Mapping\Property;

#[Model]
class ScenarioProperties
{
    #[Property]
    #[Property('[address][street_name]', scenario: 'custom')]
    private string $street;

    #[Property]
    #[Property('[address][house_number]', scenario: 'custom')]
    private string $houseNo;

    public function setStreet(string $street): void
    {
        $this->street = $street;
    }

    public function getStreet(): ?string
    {
        return $this->street ?? null;
    }

    public function setHouseNo(string $houseNo): void
    {
        $this->houseNo = $houseNo;
    }

    public function getHouseNo(): ?string
    {
        return $this->houseNo ?? null;
    }
}
