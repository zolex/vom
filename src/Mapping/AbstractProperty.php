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

namespace Zolex\VOM\Mapping;

abstract class AbstractProperty
{
    public function __construct(
        private string|bool $accessor = true,
        private ?string $field = null,
        private bool $root = false,
        private array $aliases = [],
        private bool|string|int|null $trueValue = null,
        private bool|string|int|null $falseValue = null,
        private ?string $defaultOrder = null,
        private ?string $dateTimeFormat = null,
    ) {
    }

    public function isNested(): bool
    {
        return (bool) $this->accessor;
    }

    public function isRoot(): bool
    {
        return $this->root;
    }

    public function getAccessor(): string|bool
    {
        return $this->accessor;
    }

    public function getField(): ?string
    {
        return $this->field;
    }

    public function getAliases(): array
    {
        return $this->aliases;
    }

    public function getAlias(string $name): ?string
    {
        return $this->aliases[$name] ?? null;
    }

    public function getTrueValue(): bool|string|int|null
    {
        return $this->trueValue;
    }

    public function getFalseValue(): bool|string|int|null
    {
        return $this->falseValue;
    }

    public function getDefaultOrder(): ?string
    {
        return $this->defaultOrder;
    }

    public function getDateTimeFormat(): ?string
    {
        return $this->dateTimeFormat;
    }
}
