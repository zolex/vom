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
class ModelWithFactory
{
    private string $modelName;
    private string|int $modelGroup;
    private bool $modelFlag;

    private function __construct()
    {
    }

    #[VOM\Factory]
    public static function create(
        #[VOM\Argument]
        string $name,
        #[VOM\Argument]
        string|int|null $group,
        #[VOM\Argument]
        bool $flag = true,
    ): self {
        $instance = new self();
        $instance->setModelName($name);
        $instance->setModelFlag($flag);
        if (null !== $group) {
            $instance->setModelGroup($group);
        }

        return $instance;
    }

    #[VOM\Factory(priority: 100)]
    public static function anotherCreate(
        #[VOM\Argument]
        string $somethingRequired,
    ): self {
        $instance = new self();
        $instance->setModelName($somethingRequired);

        return $instance;
    }

    #[VOM\Factory]
    public static function invalidReturn(
        #[VOM\Argument]
        bool $last,
    ): object {
        return new \stdClass();
    }

    #[VOM\Normalizer(accessor: '[name]')]
    public function getModelName(): ?string
    {
        return $this->modelName ?? null;
    }

    public function setModelName(string $modelName): void
    {
        $this->modelName = $modelName;
    }

    #[VOM\Normalizer(accessor: '[group]')]
    public function getModelGroup(): string|int|null
    {
        return $this->modelGroup ?? null;
    }

    public function setModelGroup(string|int $modelGroup): void
    {
        $this->modelGroup = $modelGroup;
    }

    #[VOM\Normalizer(accessor: '[flag]')]
    public function getModelFlag(): bool
    {
        return $this->modelFlag ?? false;
    }

    public function setModelFlag(bool $modelFlag): void
    {
        $this->modelFlag = $modelFlag;
    }
}
