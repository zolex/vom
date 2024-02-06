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

namespace Zolex\VOM\Test\Fixtures;

use Symfony\Component\Serializer\Annotation\Groups;
use Zolex\VOM\Mapping\Model;
use Zolex\VOM\Mapping\Property;

#[Model(
    presets: [
        'preset-name' => ['group-a', 'group-b'],
    ],
)]
class Person
{
    public function __construct(
        ?int $id = null,
        ?string $firstname = null,
        ?string $lastname = null,
        ?int $age = null,
        ?string $email = null,
        ?bool $isAwesome = null,
        ?bool $isHilarious = null,
        ?bool $isDelicious = null,
        ?bool $isHoly = null,
        ?Address $address = null,
    ) {
        if (null !== $id) {
            $this->id = $id;
        }
        if (null !== $firstname) {
            $this->firstname = $firstname;
        }
        if (null !== $lastname) {
            $this->lastname = $lastname;
        }
        if (null !== $age) {
            $this->age = $age;
        }
        if (null !== $email) {
            $this->email = $email;
        }
        if (null !== $isAwesome) {
            $this->isAwesome = $isAwesome;
        }
        if (null !== $isHilarious) {
            $this->isHilarious = $isHilarious;
        }
        if (null !== $isDelicious) {
            $this->isDelicious = $isDelicious;
        }
        if (null !== $isHoly) {
            $this->isHoly = $isHoly;
        }
        if (null !== $address) {
            $this->address = $address;
        }
    }

    #[Groups(['id', 'standard', 'extended'])]
    #[Property('id')]
    public int $id;

    #[Groups('standard')]
    #[Property(accessor: 'name.firstname', defaultOrder: 'DESC')]
    public string $firstname;

    #[Groups('standard')]
    #[Property(accessor: 'name.lastname')]
    public string $lastname;

    #[Groups(['standard', 'extended'])]
    #[Property('int_age', aliases: ['ageFrom' => 'int_age_min', 'ageTo' => 'int_age_max'])]
    public int $age;

    #[Groups(['standard', 'extended'])]
    #[Property('contact_email')]
    public string $email;

    #[Groups('extended')]
    #[Property('bool_awesome')]
    public bool $isAwesome;

    #[Groups(['extended', 'isHilarious'])]
    #[Property('hilarious', trueValue: 'ON', falseValue: 'OFF')]
    public bool $isHilarious;

    #[Groups('extended')]
    #[Property('delicious')]
    public bool $isDelicious;

    #[Groups(['extended', 'isHoly'])]
    #[Property('holy', trueValue: 'yes', falseValue: 'no')]
    public bool $isHoly;

    #[Groups(['extended', 'address'])]
    #[Property]
    public Address $address;
}
