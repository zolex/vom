<?php

/*
 * This file is part of the VOM package.
 *
 * (c) Andreas Linden <zlx@gmx.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zolex\VOM\Test\Fixtures;

use Symfony\Component\Serializer\Annotation\Groups;
use Zolex\VOM\Mapping\Model;
use Zolex\VOM\Mapping\Property;

#[Model]
class Address extends Thing
{
    public function __construct(
        ?string $street = null,
        ?string $houseNo = null,
        ?string $zip = null,
        ?string $city = null,
        ?string $country = null,
    ) {
        if (null !== $street) {
            $this->street = $street;
        }

        if (null !== $houseNo) {
            $this->houseNo = $houseNo;
        }

        if (null !== $zip) {
            $this->zip = $zip;
        }

        if (null !== $city) {
            $this->city = $city;
        }

        if (null !== $country) {
            $this->country = $country;
        }
    }

    #[Groups(['address', 'address.street', 'extended'])]
    #[Property()]
    private string $street;

    #[Groups(['address', 'address.houseno', 'extended'])]
    #[Property('[housenumber]')]
    private string $houseNo;

    #[Groups(['address', 'address.zipcode', 'extended'])]
    #[Property('[zipcode]')]
    private string $zip;

    #[Groups(['address', 'address.city', 'extended'])]
    #[Property()]
    private string $city;

    #[Groups(['address', 'address.country', 'extended'])]
    #[Property()]
    private string $country;

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(?string $street): void
    {
        $this->street = $street;
    }

    public function getHouseNo(): ?string
    {
        return $this->houseNo;
    }

    public function setHouseNo(?string $houseNo): void
    {
        $this->houseNo = $houseNo;
    }

    public function getZip(): ?string
    {
        return $this->zip;
    }

    public function setZip(?string $zip): void
    {
        $this->zip = $zip;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): void
    {
        $this->city = $city;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): void
    {
        $this->country = $country;
    }
}
