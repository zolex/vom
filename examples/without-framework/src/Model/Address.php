<?php

namespace App\Model;

use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class Address
{
    #[VOM\Property]
    public string $street;

    #[VOM\Property('zip')]
    public string $zipCode;

    #[VOM\Property]
    public string $city;

    #[VOM\Property('country_name')]
    public string $country;
}
