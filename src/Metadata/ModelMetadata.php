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
use Zolex\VOM\Metadata\Exception\RuntimeException;
use Zolex\VOM\Metadata\Factory\ModelMetadataFactoryInterface;

final class ModelMetadata
{
    /**
     * @var array|PropertyMetadata[]
     */
    private array $properties = [];
    /**
     * @var array|PropertyMetadata[]
     */
    private array $constructorArguments = [];
    private ?Model $attribute = null;

    /**
     * @var array|MethodCallMetadata[]
     */
    private array $methodCalls = [];

    public function __construct(private readonly string $class)
    {
    }

    public function getClass(): string
    {
        return $this->class;
    }

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

    public function hasProperty(string $name): bool
    {
        return isset($this->properties[$name]);
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

    public function hasConstructorArgument(string $name): bool
    {
        return isset($this->constructorArguments[$name]);
    }

    public function getConstructorArguments(): array
    {
        return $this->constructorArguments;
    }

    public function addConstructorArgument(PropertyMetadata $property): void
    {
        $this->constructorArguments[$property->getName()] = $property;
    }

    /**
     * @return MethodCallMetadata[]
     */
    public function getMethodCalls(): iterable
    {
        foreach ($this->methodCalls as $methodCall) {
            yield $methodCall;
        }
    }

    public function addMethodCall(MethodCallMetadata $methodCall): void
    {
        $this->methodCalls[] = $methodCall;
    }

    public function find(string $query, ModelMetadataFactoryInterface $factory): ?PropertyMetadata
    {
        $property = null;
        $path = explode('.', $query);
        $metadata = &$this;

        foreach ($path as $item) {
            if (!$property = $metadata->getProperty($item)) {
                throw new RuntimeException(sprintf('Could not find metadata path "%s" in "%s"', $query, $this->class));
            }

            if ($modelMetadata = $factory->getMetadataFor($property->getType())) {
                $metadata = &$modelMetadata;
            }
        }

        return $property;
    }
}
