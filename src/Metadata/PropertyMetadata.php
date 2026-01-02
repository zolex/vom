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

use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\Type\UnionType;
use Symfony\Component\TypeInfo\Type\WrappingTypeInterface;
use Zolex\VOM\Mapping\AbstractProperty;

class PropertyMetadata
{
    private mixed $defaultValue;

    public function __construct(
        private readonly string $name,
        private readonly Type $type,
        private readonly AbstractProperty $attribute,
    ) {
    }

    public function isNullable(): bool
    {
        return $this->type->isNullable();
    }

    public function getClass(): ?string
    {
        $t = $this->type;
        // Unwrap wrappers until we reach a base or union type
        while ($t instanceof WrappingTypeInterface) {
            $t = $t->getWrappedType();
        }
        if ($t instanceof ObjectType) {
            return $t->getClassName();
        }
        if ($t instanceof UnionType) {
            foreach ($t->getTypes() as $u) {
                while ($u instanceof WrappingTypeInterface) {
                    $u = $u->getWrappedType();
                }
                if ($u instanceof ObjectType) {
                    return $u->getClassName();
                }
            }
        }

        return null;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return non-empty-array<int|string, string>|string|null
     */
    public function getField(): array|string|null
    {
        return $this->attribute->getField() ?? (($accessor = $this->getAccessor()) ? $accessor : null);
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function hasAccessor(): bool
    {
        return $this->attribute->hasAccessor();
    }

    public function getAccessor(): string|array|false
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

    public function getExtractor(): ?string
    {
        return $this->attribute->getExtractor();
    }

    public function getMappedValue(mixed $value): mixed
    {
        $map = $this->attribute->getMap();
        if (!isset($map[$value])) {
            return $this->getDefaultValue();
        }

        return $map[$value];
    }

    public function getScenario(): string
    {
        return $this->attribute->getScenario();
    }

    public function getRelative(): int|array|null
    {
        return $this->attribute->getRelative();
    }
}
