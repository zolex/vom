<?php

namespace Zolex\VOM\Test\Fixtures;

use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class ModelFlag
{
    #[VOM\Property('text')]
    public string $label;

    #[VOM\Property('value', flag: true)]
    public bool $isEnabled;
}
