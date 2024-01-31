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
    private ?Model $attribute = null;

    public function getPreset(string $name): ?array
    {
        return $this->attribute?->getPreset($name) ?? null;
    }

    public function setAttribute(Model $attribute): void
    {
        $this->attribute = $attribute;
    }

    public function getAttribute(): ?Model
    {
        return $this->attribute;
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
}
