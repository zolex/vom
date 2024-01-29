<?php

declare(strict_types=1);

namespace Zolex\VOM\Mapping;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER)]
final class Property
{
    public function __construct(
        private ?string $accessor = null,
        private ?string $field = null,
        private bool $nested = true,
        private bool $root = false,
        private bool $isAlias = false,
        private array $aliases = [],
        private ?bool $flag = null,
        private ?string $flagOf = null,
        private bool $bool = false,
        private bool|string|int|null $trueValue = true,
        private bool|string|int|null $falseValue = false,
        private ?string $defaultOrder = null,
        private ?bool $list = null,
    ) {
        $this->list ??= $this->nested;
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
        return true === $this->flag || null !== $this->flagOf;
    }

    /**
     * @return string|null
     */
    public function getFlagOf(): ?string
    {
        return $this->flagOf;
    }

    public function getAccessor(): ?string
    {
        return $this->accessor;
    }

    public function getField(): ?string
    {
        return $this->field;
    }

    public function getFilterAccessor(): ?string
    {
        return str_replace(['.value', '.text'], '', $this->accessor);
    }

    public function isAlias(): bool
    {
        return $this->isAlias;
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

    public function isTrue(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'on', 'ON', 'yes', 'YES', 'y', 'Y'], true);
    }

    public function getFalseValue(): bool|string|int|null
    {
        return $this->falseValue;
    }

    public function isFalse(mixed $value): bool
    {
        return in_array($value, [false, null, 0, '0', 'off', 'OFF', 'no', 'NO', 'n', 'N'], true);
    }

    public function getDefaultOrder(): ?string
    {
        return $this->defaultOrder;
    }

    public function isList(): bool
    {
        return $this->list;
    }
}
