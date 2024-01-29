<?php

namespace App\ApiResource;

use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class Contact
{
    #[VOM\Property('email_address')]
    public string $email;

    #[VOM\Property]
    public string $phone;
}
