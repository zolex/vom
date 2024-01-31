<?php

namespace Zolex\VOM\Test\Fixtures;

use Zolex\VOM\Mapping\Model;
use Zolex\VOM\Mapping\Property;

#[Model]
class ArrayWithoutDocTag
{
    #[Property]
    public array $list;
}
