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

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER)]
final class Property
{
    public function __construct(
        private ?string $accessor = null,
        private ?string $field = null,
        private bool $nested = true,
        private bool $root = false,
        private array $aliases = [],
        private bool $flag = false,
        private bool|string|int|null $trueValue = null,
        private bool|string|int|null $falseValue = null,
        private ?string $defaultOrder = null,
        private ?string $dateTimeFormat = null,
    ) {
        $this->field ??= $this->accessor;
    }

    public function isNested(): bool
    {
        return $this->nested;
    }

    public function isRoot(): bool
    {
        return $this->root;
    }

    public function isFlag(): bool
    {
        return $this->flag;
    }

    public function getAccessor(): ?string
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
