<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Tests\Fixtures\Metadata\Get;
use App\State\PersonStateProvider;
use Zolex\VOM\Mapping as VOM;

#[Get(provider: PersonStateProvider::class)]
#[Post]
#[Post(
    uriTemplate: '/people/legacy',
    normalizationContext: ['vom' => true],
    denormalizationContext: ['vom' => true],
)]
#[VOM\Model]
class Person
{
    #[ApiProperty(identifier: true)]
    #[VOM\Property]
    public int $id;

    #[VOM\Property]
    public string $firstname;

    #[VOM\Property('surname')]
    public string $lastname;

    #[ApiProperty(genId: false)]
    #[VOM\Property(nested: false)]
    public Address $address;

    #[ApiProperty(genId: false)]
    #[VOM\Property(nested: false)]
    public Contact $contact;
}