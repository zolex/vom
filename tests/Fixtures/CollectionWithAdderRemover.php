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

use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class CollectionWithAdderRemover
{
    /**
     * @var \ArrayObject<Person>
     */
    #[VOM\Property]
    private \ArrayAccess $people;

    public function __construct()
    {
        $this->people = new \ArrayObject();
    }

    public function getPeople(): \ArrayAccess
    {
        return $this->people;
    }

    public function addPerson(Person $person): void
    {
        $this->people->append($person);
    }

    public function removePerson(Person $person): void
    {
    }
}
