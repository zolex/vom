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
        private readonly ?string $arrayType = null,
        private ?ModelMetadata $modelMetadata = null,
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
        return $this->attribute->getDateTimeFormat() ?? \DateTime::RFC3339_EXTENDED;
    }
}
