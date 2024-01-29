<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Tests\Fixtures\Metadata\Get;
use App\State\PersonStateProvider;
use Zolex\VOM\Mapping as VOM;

#[ApiResource()]
#[Get(provider: PersonStateProvider::class)]
#[VOM\Model]
class Person
{
    #[ApiProperty(identifier: true)]
    public int $id;

    #[VOM\Property]
    public string $firstname;

    #[VOM\Property('surname')]
    public string $lastname;

    #[VOM\Property(nested: false)]
    public Address $address;

    #[VOM\Property(nested: false)]
    public Contact $contact;
}
