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

namespace Zolex\VOM\Metadata;

use Symfony\Component\PropertyInfo\Type;
use Zolex\VOM\Mapping\AbstractProperty;

class PropertyMetadata
{
    private readonly mixed $defaultValue;
    private bool $nullable = false;

    public function __construct(
        private readonly string $name,
        /* @var array|Type[] */
        private array $types,
        private readonly AbstractProperty $attribute,
    ) {
        foreach ($this->types as $type) {
            if ($type->isNullable()) {
                $this->nullable = true;
                break;
            }
        }
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function getClass(): ?string
    {
        foreach ($this->types as $type) {
            if ($class = $type->getClassName()) {
                return $class;
            }
        }

        return null;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getField(): ?string
    {
        return $this->attribute->getField() ?? (($accessor = $this->getAccessor()) ? $accessor : null);
    }

    /**
     * @return array|Type[]
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    public function hasAccessor(): bool
    {
        return $this->attribute->hasAccessor();
    }

    public function getAccessor(): string|false
    {
        $accessor = $this->attribute->getAccessor();
        if (false === $accessor = (true === $accessor ? '['.$this->name.']' : $accessor)) {
            return false;
        }

        return $accessor;
    }

    public function getAliases(): array
    {
        return $this->attribute->getAliases();
    }

    public function getAlias(string $name): ?string
    {
        return $this->attribute->getAlias($name);
    }

    public function isRoot(): bool
    {
        return $this->attribute->isRoot();
    }

    public function getTrueValue(): bool|string|int|null
    {
        return $this->attribute->getTrueValue();
    }

    public function getFalseValue(): bool|string|int|null
    {
        return $this->attribute->getFalseValue();
    }

    public function getDefaultOrder(): ?string
    {
        return $this->attribute->getDefaultOrder();
    }

    public function getDateTimeFormat(): string
    {
        return $this->attribute->getDateTimeFormat() ?? \DateTimeInterface::RFC3339_EXTENDED;
    }

    public function getDefaultValue(): mixed
    {
        return $this->defaultValue;
    }

    public function setDefaultValue(mixed $defaultValue): void
    {
        $this->defaultValue = $defaultValue;
    }

    public function hasDefaultValue(): bool
    {
        return isset($this->defaultValue);
    }

    public function hasMap(): bool
    {
        return $this->attribute->hasMap();
    }

    public function isSerialized(): bool
    {
        return $this->attribute->isSerialized();
    }

    public function getMappedValue(mixed $value): mixed
    {
        $map = $this->attribute->getMap();
        if (!isset($map[$value])) {
            return $this->getDefaultValue();
        }

        return $map[$value];
    }
}
