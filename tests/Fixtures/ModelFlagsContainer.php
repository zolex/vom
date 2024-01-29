<?php

namespace Zolex\VOM\Test\Fixtures;

use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class ModelFlagsContainer
{
    #[VOM\Property]
    public ModelFlag $flagA;

    #[VOM\Property]
    public ModelFlag $flagB;

    #[VOM\Property]
    public ?ModelFlag $flagC = null;
}
