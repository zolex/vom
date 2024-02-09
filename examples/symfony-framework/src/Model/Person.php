<?php

namespace App\Model;

use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class Person
{
    #[VOM\Property]
    public string $firstname;

    #[VOM\Property('[surname]')]
    public string $lastname;

    #[VOM\Property(accessor: false)]
    public Address $address;

    #[VOM\Property(accessor: false)]
    public Contact $contact;
}
