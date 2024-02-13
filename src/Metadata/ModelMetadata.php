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
     * @var array|DenormalizerMetadata[]
     */
    private array $denormalizers = [];

    /**
     * @var array|NormalizerMetadata[]
     */
    private array $normalizers = [];

    public function __construct(private readonly string $class, private bool $isInstantiable = true)
    {
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function isInstantiable(): bool
    {
        return $this->isInstantiable;
    }

    public function setAttribute(Model $attribute): void
    {
        $this->attribute = $attribute;
    }

    public function getAttribute(): ?Model
    {
        return $this->attribute;
    }

    public function hasAttribute(): bool
    {
        return null !== $this->attribute;
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
     * @return DenormalizerMetadata[]
     */
    public function getDenormalizers(): iterable
    {
        foreach ($this->denormalizers as $denormalizer) {
            yield $denormalizer;
        }
    }

    public function addDenormalizer(DenormalizerMetadata $denormalizerMetadata): void
    {
        $this->denormalizers[] = $denormalizerMetadata;
    }

    /**
     * @return NormalizerMetadata[]
     */
    public function getNormalizers(): iterable
    {
        foreach ($this->normalizers as $normalizer) {
            yield $normalizer;
        }
    }

    public function addNormalizer(NormalizerMetadata $normalizerMetadata): void
    {
        $this->normalizers[] = $normalizerMetadata;
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

            if (($class = $property->getClass()) && ($modelMetadata = $factory->getMetadataFor($class))) {
                $metadata = &$modelMetadata;
            }
        }

        return $property;
    }
}
