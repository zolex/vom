<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\AddressRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Zolex\VOM\Mapping as VOM;

#[ORM\Entity(repositoryClass: AddressRepository::class)]
#[ApiResource]

#[VOM\Model]
class Address
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['address'])]
    #[VOM\Property('[ADDRESS_IDENT]')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['address'])]
    #[VOM\Property('[STREET]')]
    private ?string $street = null;

    #[ORM\Column(length: 255)]
    #[Groups(['address'])]
    #[VOM\Property('[ZIP_CODE]')]
    private ?string $zipCode = null;

    #[ORM\Column(length: 255)]
    #[Groups(['address'])]
    #[VOM\Property('[COUNTRY_CODE]')]
    private ?string $country = null;

    #[ORM\Column(length: 255)]
    #[Groups(['address'])]
    #[VOM\Property('[LOCATION]')]
    private ?string $city = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(string $street): static
    {
        $this->street = $street;

        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(string $zipCode): static
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }
}
