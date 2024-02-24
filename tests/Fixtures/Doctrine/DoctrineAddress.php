<?php

declare(strict_types=1);

/*
 * This file is part of the VOM package.
 *
 * (c) Andreas Linden <zlx@gmx.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zolex\VOM\Test\Fixtures\Doctrine;

use App\Repository\AddressRepository;
use Doctrine\ORM\Mapping as ORM;
use Zolex\VOM\Mapping as VOM;

#[ORM\Entity(repositoryClass: AddressRepository::class)]
#[VOM\Model]
class DoctrineAddress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[VOM\Property]
    private ?string $street = null;

    #[ORM\ManyToOne(inversedBy: 'addresses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?DoctrinePerson $person = null;

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

    public function getPerson(): ?DoctrinePerson
    {
        return $this->person;
    }

    public function setPerson(?DoctrinePerson $person): static
    {
        $this->person = $person;

        return $this;
    }
}
