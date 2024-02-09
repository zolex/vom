<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\PersonRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Zolex\VOM\Mapping as VOM;

#[ApiResource]
#[ORM\Entity(repositoryClass: PersonRepository::class)]

#[GetCollection(normalizationContext: ['groups' => ['person']])]

// adds another GET collection endpoint, that transforms the resource using VOM
#[GetCollection(
    uriTemplate: '/people/legacy',
    normalizationContext: [
        'groups' => ['person', 'address', 'contact'],
        'vom' => true,
    ]
)]

#[Get(
    normalizationContext: [
        'groups' => ['person', 'address', 'contact'],
    ])
]

// adds another GET item endpoint, that transforms the resource using VOM
#[Get(
    uriTemplate: '/people/legacy/{id}',
    normalizationContext: [
        'groups' => ['person', 'address', 'contact'],
        'vom' => true,
    ])
]
#[Post(
    normalizationContext: [
        'groups' => ['person', 'address', 'contact'],
    ],
    denormalizationContext: [
        'groups' => ['person', 'address', 'contact'],
    ]
)]

// adds another POST endpoint that accepts the input format mapped using VOM
#[Post(
    uriTemplate: '/people/legacy',
    normalizationContext: [
        'groups' => ['person', 'address', 'contact'],
    ],
    denormalizationContext: [
        'groups' => ['person', 'address', 'contact'],
        'vom' => true,
    ]
)]
#[Put]
#[Delete]
#[Patch]
#[VOM\Model]
class Person
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['person'])]
    #[VOM\Property('[IDENT]')]
    private ?int $id = null;

    #[Groups(['person'])]
    #[ORM\Column(length: 255)]
    #[VOM\Property('[FIRST_NAME]')]
    private ?string $firstname = null;

    #[Groups(['person'])]
    #[ORM\Column(length: 255)]
    #[VOM\Property('[LAST_NAME]')]
    private ?string $lastname = null;

    #[Groups(['person', 'address'])]
    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[VOM\Property(accessor: false)]
    private ?Address $address = null;

    #[Groups(['person', 'contact'])]
    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[VOM\Property(accessor: false)]
    private ?Contact $contact = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function setAddress(?Address $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function setContact(?Contact $contact): static
    {
        $this->contact = $contact;

        return $this;
    }
}
