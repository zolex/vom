<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Tests\Fixtures\Metadata\Get;
use App\State\PersonStateProcessor;
use App\State\PersonStateProvider;
use Zolex\VOM\Mapping as VOM;

#[Get(
    provider: PersonStateProvider::class
)]

#[Get(
    uriTemplate: '/people/{id}/legacy',
    normalizationContext: ['vom' => true],
    provider: PersonStateProvider::class
)]

#[Post]

#[Post(
    uriTemplate: '/people/legacy',
    normalizationContext: ['vom' => true],
    denormalizationContext: ['vom' => true],
    provider: PersonStateProvider::class,
    processor: PersonStateProcessor::class,
)]

#[VOM\Model]
class Person
{
    #[ApiProperty(identifier: true)]
    #[VOM\Property]
    public int $id;

    #[VOM\Property]
    public string $firstname;

    #[VOM\Property('[surname]')]
    public string $lastname;

    #[ApiProperty(genId: false)]
    #[VOM\Property(accessor: false)]
    public Address $address;

    #[ApiProperty(genId: false)]
    #[VOM\Property(accessor: false)]
    public Contact $contact;
}
