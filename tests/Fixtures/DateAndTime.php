<?php

namespace Zolex\VOM\Test\Fixtures;

use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class DateAndTime
{
    #[VOM\Property(dateTimeFormat: 'Y-m-d H:i:s')]
    public \DateTime $dateTime;

    #[VOM\Property(dateTimeFormat: 'Y-m-d H:i:s')]
    public \DateTimeImmutable $dateTimeImmutable;
}
