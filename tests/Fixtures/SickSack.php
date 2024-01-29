<?php

namespace Zolex\VOM\Test\Fixtures;

use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class SickSack
{
    #[VOM\Property]
    public int $sick;

    #[VOM\Property]
    public string $sack;

    #[VOM\Property(nested: false)]
    public SickSuck $sickSuck;
}
