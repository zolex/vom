<?php

namespace Zolex\VOM\Test\Fixtures;

use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class Booleans
{
    #[VOM\Property]
    public bool $bool;

    #[VOM\Property]
    public ?bool $nullableBool;
}
