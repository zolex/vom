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
use Zolex\VOM\Mapping\Property;

class PropertyMetadata
{
    public const BUILTIN_CLASSES = [
        \DateTime::class,
        \DateTimeImmutable::class,
    ];

    private ?ModelMetadata $modelMetadata = null;
    private readonly mixed $defaultValue;

    public function __construct(
        private readonly string $name,
        /** @var array|Type[] */
        private readonly iterable $types,
        private readonly Property $attribute,
        private readonly array $groups = ['default'],
        private readonly bool $isConstructorArgument = false,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getField(): ?string
    {
        return $this->attribute->getField() ?? $this->attribute->getAccessor();
    }

    public function getType(): ?string
    {
        foreach ($this->types as $type) {
            if ($class = $type->getClassName()) {
                return $class;
            }
        }

        return null;
    }

    public function getCollectionType(): ?string
    {
        foreach ($this->types as $type) {
            if ($type->isCollection()) {
                foreach ($type->getCollectionValueTypes() as $collectionType) {
                    return $collectionType->getClassName();
                }
            }
        }

        return null;
    }

    public function getGroups(): array
    {
        return $this->groups;
    }

    public function isBool(): bool
    {
        foreach ($this->types as $type) {
            if ('bool' === $type->getBuiltinType()) {
                return true;
            }
        }

        return false;
    }

    public function isDateTime(): bool
    {
        foreach ($this->types as $type) {
            $class = $type->getClassName();
            if (\DateTime::class === $class || \DateTimeImmutable::class === $class) {
                return true;
            }
        }

        return false;
    }

    public function isFlag(): bool
    {
        return $this->attribute->isFlag();
    }

    public function getAccessor(): ?string
    {
        return $this->attribute->getAccessor() ?? $this->name;
    }

    public function getAliases(): array
    {
        return $this->attribute->getAliases();
    }

    public function getAlias(string $name): ?string
    {
        return $this->attribute->getAlias($name);
    }

    public function isNested(): bool
    {
        return $this->attribute->isNested();
    }

    public function isModel(): bool
    {
        return null !== $this->modelMetadata;
    }

    public function isBuiltinClass(): bool
    {
        foreach ($this->types as $type) {
            if (\in_array($type->getClassName(), self::BUILTIN_CLASSES)) {
                return true;
            }
        }

        return false;
    }

    public function isCollection(): bool
    {
        foreach ($this->types as $type) {
            if ($type->isCollection()) {
                return true;
            }
        }

        return false;
    }

    public function isRoot(): bool
    {
        return $this->attribute->isRoot();
    }

    public function getModelMetadata(): ?ModelMetadata
    {
        return $this->modelMetadata;
    }

    public function setModelMetadata(?ModelMetadata $modelMetadata): void
    {
        $this->modelMetadata = $modelMetadata;
    }

    public function getTrueValue(): bool|string|int|null
    {
        return $this->attribute->getTrueValue();
    }

    public function getFalseValue(): bool|string|int|null
    {
        return $this->attribute->getFalseValue();
    }

    public function isTrue(mixed $value): bool
    {
        return $this->attribute->isTrue($value);
    }

    public function isFalse(mixed $value): bool
    {
        return $this->attribute->isFalse($value);
    }

    public function getDefaultOrder(): ?string
    {
        return $this->attribute->getDefaultOrder();
    }

    public function getArrayType(): ?string
    {
        return $this->arrayType;
    }

    public function getDateTimeFormat(): string
    {
        return $this->attribute->getDateTimeFormat() ?? \DateTimeInterface::RFC3339_EXTENDED;
    }

    public function isConstructorArgument(): bool
    {
        return $this->isConstructorArgument;
    }

    public function isNullable(): bool
    {
        foreach ($this->types as $type) {
            return $type->isNullable();
        }

        return false;
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

    // to get nested metadata with property-accessor
    public function __get($name): mixed
    {
        return $this->modelMetadata?->{$name} ?? null;
    }
}
