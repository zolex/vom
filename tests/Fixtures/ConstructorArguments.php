<?php

namespace Zolex\VOM\Test\Fixtures;

use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class ConstructorArguments
{
    private int $id;
    private string $name;
    private ?bool $nullable;
    private bool $default;

    public function __construct(
        #[VOM\Property]
        int $id,
        #[VOM\Property]
        string $name,
        #[VOM\Property]
        ?bool $nullable,
        #[VOM\Property]
        bool $default = true
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->nullable = $nullable;
        $this->default = $default;
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
