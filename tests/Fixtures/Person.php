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

use Symfony\Component\Serializer\Attribute\Groups;
use Zolex\VOM\Mapping\Model;
use Zolex\VOM\Mapping\Property;

#[Model]
class Person extends Thing implements SomeInterface
{
    use GenericFactoryTrait;

    #[Groups(['id', 'standard', 'extended'])]
    #[Property('[id]')]
    public int $id;

    #[Groups('standard')]
    #[Property(accessor: '[name][firstname]', defaultOrder: 'DESC')]
    public string $firstname;

    #[Groups('standard')]
    #[Property(accessor: '[name][lastname]')]
    public string $lastname;

    #[Groups(['standard', 'extended'])]
    #[Property('[int_age]', aliases: ['ageFrom' => 'int_age_min', 'ageTo' => 'int_age_max'])]
    public int $age;

    #[Groups(['standard', 'extended'])]
    #[Property('[contact_email]')]
    public string $email;

    #[Groups('extended')]
    #[Property('[bool_awesome]')]
    public bool $isAwesome;

    #[Groups(['extended', 'isHilarious'])]
    #[Property('[hilarious]', trueValue: 'ON', falseValue: 'OFF')]
    public bool $isHilarious;

    #[Groups('extended')]
    #[Property('[delicious]')]
    public bool $isDelicious;

    #[Groups(['extended', 'isHoly'])]
    #[Property('[holy]', trueValue: 'yes', falseValue: 'no')]
    public bool $isHoly;

    #[Groups(['extended', 'address'])]
    #[Property]
    public Address $address;
}
