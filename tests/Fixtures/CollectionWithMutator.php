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
class CollectionWithMutator
{
    /**
     * @var \ArrayObject<Person>
     */
    #[VOM\Property]
    private \ArrayAccess $people;

    public function getPeople(): \ArrayAccess
    {
        return $this->people;
    }

    public function setPeople(array $people): void
    {
        $this->people = new \ArrayObject($people);
    }
}
