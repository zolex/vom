<?php

namespace Zolex\VOM\Test\Fixtures;

use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class DateAndTime
{
    #[VOM\Property]
    public \DateTime $dateTime;

    #[VOM\Property]
    public \DateTimeImmutable $dateTimeImmutable;
}
