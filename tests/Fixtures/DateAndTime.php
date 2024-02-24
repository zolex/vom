<?php

declare(strict_types=1);

/*
 * This file is part of the VOM package.
 *
 * (c) Andreas Linden <zlx@gmx.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zolex\VOM\Test\Fixtures;

use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class DateAndTime
{
    #[Context(normalizationContext: [DateTimeNormalizer::FORMAT_KEY => 'Y-m-d H:i:s'])]
    #[VOM\Property()]
    public \DateTime $dateTime;

    #[Context(normalizationContext: [DateTimeNormalizer::FORMAT_KEY => 'Y-m-d H:i:s'])]
    #[VOM\Property()]
    public \DateTimeImmutable $dateTimeImmutable;
}
