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

use Zolex\VOM\Mapping\Model;

final class ModelMetadata
{
    /**
     * @var array|PropertyMetadata[]
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

    public function hasProperty(string $name): bool
    {
        return isset($this->properties[$name]);
    }

    public function addProperty(PropertyMetadata $property): void
    {
        $this->properties[$property->getName()] = $property;
    }

    // to get nested metadata with property-accessor
    public function __get($name): mixed
    {
        return $this->properties[$name] ?? null;
    }

    public function getConstructorArguments(): iterable
    {
        foreach ($this->properties as $property) {
            if ($property->isConstructorArgument()) {
                yield $property;
            }
        }
    }
}
