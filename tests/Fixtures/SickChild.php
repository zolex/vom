<?php

namespace Zolex\VOM\Test\Fixtures;

use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class SickChild
{
    #[VOM\Property('name.first')]
    public string $firstname;

    #[VOM\Property]
    public bool $hasHair;
}
