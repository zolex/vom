<?php

namespace Zolex\VOM\Test\Fixtures;

use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class CommonFlags
{
    #[VOM\Property(flag: true)]
    public bool $flagA;

    #[VOM\Property(flag: true)]
    public bool $flagB;

    #[VOM\Property(flag: true)]
    public ?bool $flagC = null;
}
