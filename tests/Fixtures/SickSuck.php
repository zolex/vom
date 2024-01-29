<?php

namespace Zolex\VOM\Test\Fixtures;

use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class SickSuck
{
    #[VOM\Property('SICK_FROM_ROOT')]
    public string $sickedy;

    #[VOM\Property('SACK_FROM_ROOT')]
    public string $sackedy;
}
