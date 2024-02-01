<?php

namespace Zolex\VOM\Test\Fixtures;

use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class PropertyPromotion
{
    public function __construct(
        #[VOM\Property]
        private int $id,
        #[VOM\Property]
        private string $name,
        #[VOM\Property]
        private ?bool $nullable,
        #[VOM\Property]
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
