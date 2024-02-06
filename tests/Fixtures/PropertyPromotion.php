<?php

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
class PropertyPromotion
{
    public function __construct(
        #[VOM\Argument]
        private int $id,
        #[VOM\Argument]
        private string $name,
        #[VOM\Argument]
        private ?bool $nullable,
        #[VOM\Argument]
        private bool $default = true,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNullable(): ?bool
    {
        return $this->nullable;
    }

    public function getDefault(): ?bool
    {
        return $this->default;
    }
}
