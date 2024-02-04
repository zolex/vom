<?php

/*
 * This file is part of the VOM package.
 *
 * (c) Andreas Linden <zlx@gmx.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zolex\VOM\Test\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class InstantiableNestedCollection
{
    /**
     * @var ArrayCollection|Collection|Person[]
     */
    #[VOM\Property]
    public Collection $people;
}
