<?php

declare(strict_types=1);

namespace Zolex\VOM\Metadata;

use Symfony\Component\Serializer\Annotation\Groups;
use Zolex\VOM\Mapping\Property;

class PropertyMetadata
{
    public function __construct(
        private readonly string $name,
        private readonly string $type,
        private readonly Property $attribute,
        private readonly ?Groups $groups = null,
        private readonly array $path = [],
        private readonly ?string $arrayType = null,
        private readonly ?PropertyMetadata $parentPropertyMetadata = null,
        private ?ModelMetadata $modelMetadata = null,
    ) {
    }

    public function __get($name): mixed
    {
        return $this->modelMetadata?->{$name} ?? null;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getField(): ?string
    {
        return $this->attribute->getField() ?? $this->attribute->getAccessor();
    }

    public function getType(): string
    {
        return trim('array' === $this->type ? $this->arrayType : $this->type, '?[]');
    }

    public function getGroups(): array
    {
        return $this->groups?->getGroups() ?? ['default'];
    }

    public function isBool(): bool
    {
        return 'bool' === $this->type;
    }

    public function isDateTime(): bool
    {
        return \DateTime::class === $this->type || \DateTimeImmutable::class === $this->type;
    }

    public function isFlag(): bool
    {
        return $this->attribute->isFlag();
    }

    public function getFlagOf(): ?string
    {
        return $this->attribute->getFlagOf();
    }

    public function getAccessor(): ?string
    {
        return $this->attribute->getAccessor() ?? $this->name;
    }

    public function getFilterAccessor(): ?string
    {
        return $this->attribute->getFilterAccessor();
    }

    public function isAlias(): bool
    {
        return $this->attribute->isAlias();
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

    public function isArray(): bool
    {
        return 'array' === $this->type;
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

    public function getFlag(): bool
    {
        return $this->attribute->getFlag();
    }

    public function getTrueValue(): bool|string|int|null
    {
        return $this->attribute->getTrueValue();
    }

    public function isTrue(mixed $value): bool
    {
        return $this->attribute->isTrue($value);
    }

    public function isFalse(mixed $value): bool
    {
        return $this->attribute->isFalse($value);
    }

    public function getFalseValue(): bool|string|int|null
    {
        return $this->attribute->getFalseValue();
    }

    public function getDefaultOrder(): ?string
    {
        return $this->attribute->getDefaultOrder();
    }

    public function getPath(): array
    {
        return $this->path;
    }

    public function getArrayType(): ?string
    {
        return $this->arrayType;
    }

    public function getParentPropertyMetadata(): ?PropertyMetadata
    {
        return $this->parentPropertyMetadata;
    }

    public function includeInList(): bool
    {
        return $this->attribute->isList();
    }

    public function getDateTimeFormat(): string
    {
        return $this->attribute->getDateTimeFormat() ?? \DateTime::RFC3339_EXTENDED;
    }
}
