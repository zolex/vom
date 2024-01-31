<?php

namespace Zolex\VOM\Test\Fixtures;

use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class Arrays
{
    /**
     * @var DateAndTime[]
     */
    #[VOM\Property]
    public array $dateTimeList;

    /**
     * @var Arrays[]
     */
    #[VOM\Property]
    public array $recursiveList;
}