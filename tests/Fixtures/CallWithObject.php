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
class CallWithObject
{
    private string $name;

    #[VOM\Denormalizer]
    public function denormalizeThing(
        #[VOM\Argument('[thing]')]
        object $input,
    ): void {
        if (property_exists($input, 'name')) {
            $this->name = $input->name;
        }
    }

    public function getName(): ?string
    {
        return $this->name ?? null;
    }
}
