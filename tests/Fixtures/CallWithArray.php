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

use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class CallWithArray
{
    /**
     * @var \DateTime[]
     */
    private array $dates;

    #[VOM\Denormalizer(allowNonScalarArguments: true)]
    public function denormalizeDates(
        #[VOM\Argument('[dates]')]
        array $input,
    ): void {
        foreach ($input as $date) {
            $this->dates[] = new \DateTime($date);
        }
    }

    public function getDates(): array
    {
        return $this->dates;
    }
}
