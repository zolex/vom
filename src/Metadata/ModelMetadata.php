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

use Generator;
use Zolex\VOM\Mapping\Model;
use Zolex\VOM\Metadata\Exception\RuntimeException;
use Zolex\VOM\Metadata\Factory\ModelMetadataFactoryInterface;

final class ModelMetadata
{
    public const DEFAULT_SCENARIO = 'default';

    /**
     * @var array|PropertyMetadata[]
     */
    private array $properties = [self::DEFAULT_SCENARIO => []];
    /**
     * @var array|ArgumentMetadata[][]
     */
    private array $constructorArguments = [];

    /**
     * @var array|DependencyInjectionMetadata[]
     */
    private array $constructorDependencies = [];

    private ?Model $attribute = null;

    /**
     * @var array|DenormalizerMetadata[]
     */
    private array $denormalizers = [];

    /**
     * @var array|NormalizerMetadata[]
     */
    private array $normalizers = [];

    /**
     * @var array|FactoryMetadata[]
     */
    private array $factories = [];

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

    /**
     * @return PropertyMetadata[]
     */
    public function getProperties(string $scenario = self::DEFAULT_SCENARIO): array
    {
        return $this->properties[$scenario] ?? [];
    }

    public function getProperty(string $name, string $scenario = self::DEFAULT_SCENARIO): ?PropertyMetadata
    {
        return $this->properties[$scenario] ? $this->properties[$scenario][$name] ?? null : null;
    }

    public function addProperty(PropertyMetadata $property): void
    {
        $scenario = $property->getScenario();
        if (!isset($this->properties[$scenario])) {
            $this->properties[$scenario] = [];
        }

        $this->properties[$scenario][$property->getName()] = $property;
    }

    /**
     * @return ArgumentMetadata[]
     */
    public function getConstructorArguments(string $scenario = self::DEFAULT_SCENARIO): array
    {
        return array_merge($this->constructorArguments[$scenario] ?? [], $this->constructorDependencies);
    }

    /**
     * Adds a constructor argument to the model, so it can be injected when instantiating the model.
     *
     * {@see PropertyMetadata}
     */
    public function addConstructorArgument(PropertyMetadata $property): void
    {
        $scenario = $property->getScenario();
        if (!isset($this->constructorArguments[$scenario])) {
            $this->constructorArguments[$scenario] = [];
        }

        $this->constructorArguments[$scenario][$property->getName()] = $property;
    }

    /**
     * Adds a constructor argument to the model, that is a registered dependency.
     */
    public function addConstructorDependency(DependencyInjectionMetadata $dependency): void
    {
        $this->constructorDependencies[$dependency->getName()] = $dependency;
    }

    /**
     * @return Generator<int, DenormalizerMetadata>
     */
    public function getDenormalizers(): iterable
    {
        foreach ($this->denormalizers as $denormalizer) {
            yield $denormalizer;
        }
    }

    /**
     * Adds normalizer metadata to the model, so it can be called during denormalization.
     *
     * {@see DenormalizerMetadata}
     */
    public function addDenormalizer(DenormalizerMetadata $denormalizerMetadata): void
    {
        $this->denormalizers[] = $denormalizerMetadata;
    }

    /**
     * @return Generator<int, NormalizerMetadata>
     */
    public function getNormalizers(string $scenario = self::DEFAULT_SCENARIO): iterable
    {
        foreach ($this->normalizers[$scenario] ?? [] as $normalizer) {
            yield $normalizer;
        }
    }

    /**
     * Adds normalizer metadata to the model, so it can be called during normalization.
     *
     * {@see NormalizerMetadata}
     */
    public function addNormalizer(NormalizerMetadata $normalizerMetadata): void
    {
        $scenario = $normalizerMetadata->getScenario();
        if (!isset($this->normalizers[$scenario])) {
            $this->normalizers[$scenario] = [];
        }

        $this->normalizers[$scenario][] = $normalizerMetadata;
    }

    /**
     * @return Generator<int, FactoryMetadata>
     */
    public function getFactories(): iterable
    {
        foreach ($this->factories as $factory) {
            yield $factory;
        }
    }

    /**
     * Adds factory metadata to the model, so it can be used to instantiate a model.
     * If a factory with the same priority already exists, the new one to be added
     * will be less prioritized.
     *
     * {@See FactoryMetadata}
     */
    public function addFactory(FactoryMetadata $factory): void
    {
        $priority = $factory->getPriority();
        while (isset($this->factories[$priority])) {
            --$priority;
        }

        $this->factories[$priority] = $factory;
        krsort($this->factories, \SORT_NUMERIC);
    }

    /**
     * Recursively finds nested property metadata. If metadata does not exist it will be created.
     *
     * A use-case could be to map API-Platform style HTTP query params to the VOM normalized field names.
     */
    public function find(string $query, ModelMetadataFactoryInterface $factory): ?PropertyMetadata
    {
        $property = null;
        $path = explode('.', $query);
        $metadata = &$this;

        foreach ($path as $item) {
            if (!$property = $metadata->getProperty($item)) {
                throw new RuntimeException(\sprintf('Could not find metadata path "%s" in "%s"', $query, $this->class));
            }

            if (($class = $property->getClass()) && ($modelMetadata = $factory->getMetadataFor($class))) {
                $metadata = &$modelMetadata;
            }
        }

        return $property;
    }
}
