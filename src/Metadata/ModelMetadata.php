<?php

declare(strict_types=1);

namespace Zolex\VOM\Metadata;

use Zolex\VOM\Mapping\Model;
use Zolex\VOM\Mapping\Property;

final class ModelMetadata
{
    /**
     * @var array|Property[]
     */
    private array $properties = [];

    public function __construct(private readonly string $class)
    {
    }

    private ?Model $attribute = null;

    private ?PropertyMetadata $propertyMetadata = null;

    public function setPropertyMetadata(?PropertyMetadata $propertyMetadata): void
    {
        $this->propertyMetadata = $propertyMetadata;
    }

    public function getPreset(string $name): ?array
    {
        return $this->attribute?->getPreset($name) ?? null;
    }

    /**
     * @return PropertyMetadata|null
     */
    public function getPropertyMetadata(): ?PropertyMetadata
    {
        return $this->propertyMetadata;
    }

    public function setAttribute(Model $attribute): void
    {
        $this->attribute = $attribute;
    }

    public function getAttribute(): ?Model
    {
        return $this->attribute;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @return PropertyMetadata[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getProperty(string $name): ?PropertyMetadata
    {
        return $this->properties[$name] ?? null;
    }

    public function addProperty(PropertyMetadata $property): void
    {
        $this->properties[$property->getName()] = $property;
    }

    public function getDefaultTrueValue(): int|string|null
    {
        return $this->model?->getDefaultTrueValue() ?? null;
    }

    public function getDefaultFalseValue(): int|string|null
    {
        return $this->model?->getDefaultFalseValue() ?? null;
    }

    public function __get($name): mixed
    {
        return $this->properties[$name] ?? null;
    }
}
