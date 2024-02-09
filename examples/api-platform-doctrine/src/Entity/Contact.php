<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ContactRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Zolex\VOM\Mapping as VOM;

#[ORM\Entity(repositoryClass: ContactRepository::class)]
#[ApiResource]
#[VOM\Model]
class Contact
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['contact'])]
    #[VOM\Property('[CONTACT_IDENT]')]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['contact'])]
    #[VOM\Property('[EMAIL_ADDR]')]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Groups(['contact'])]
    #[VOM\Property('[PHONE_NUMBER]')]
    private ?string $phone = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }
}
