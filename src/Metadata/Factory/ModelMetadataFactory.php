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

namespace Zolex\VOM\Metadata\Factory;

use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Zolex\VOM\Mapping\AbstractProperty;
use Zolex\VOM\Mapping\Argument;
use Zolex\VOM\Mapping\Denormalizer;
use Zolex\VOM\Mapping\Factory;
use Zolex\VOM\Mapping\Model;
use Zolex\VOM\Mapping\Normalizer;
use Zolex\VOM\Metadata\ArgumentMetadata;
use Zolex\VOM\Metadata\DenormalizerMetadata;
use Zolex\VOM\Metadata\DependencyInjectionMetadata;
use Zolex\VOM\Metadata\Exception\MappingException;
use Zolex\VOM\Metadata\Exception\MissingMetadataException;
use Zolex\VOM\Metadata\Exception\MissingTypeException;
use Zolex\VOM\Metadata\FactoryMetadata;
use Zolex\VOM\Metadata\ModelMetadata;
use Zolex\VOM\Metadata\NormalizerMetadata;
use Zolex\VOM\Metadata\PropertyMetadata;

/**
 * Returns a {@see ModelMetadata}.
 */
class ModelMetadataFactory implements ModelMetadataFactoryInterface
{
    /**
     * @var ModelMetadata[]
     */
    private array $localCache = [];

    /**
     * @var object[]
     */
    private array $denormalizerDependencies = [];

    public function __construct(
        private readonly PropertyInfoExtractorInterface $propertyInfoExtractor,
    ) {
    }

    public function injectDenormalizerDependency(object $service): void
    {
        if (!\in_array($service, $this->denormalizerDependencies)) {
            $this->denormalizerDependencies[] = $service;
        }
    }

    /**
     * If the method was called with the same class name before,
     * the same metadata instance is returned.
     *
     * Otherwise, a new metadata instance is created.
     *
     * @throws MappingException         When invalid mapping is configured on the model class
     * @throws MissingMetadataException When the class does not exist or the VOM\Model attribute is missing
     */
    public function getMetadataFor(string|\ReflectionClass $class, ?ModelMetadata $modelMetadata = null): ?ModelMetadata
    {
        if (\is_string($class)) {
            if (\array_key_exists($class, $this->localCache)) {
                return $this->localCache[$class];
            }

            try {
                $class = new \ReflectionClass(trim($class, '?'));
            } catch (\ReflectionException $e) {
                throw new MissingMetadataException(\sprintf('Can not create Model metadata for "%s". %s', $class, $e->getMessage()));
            }
        }

        if (null === $modelMetadata) {
            $modelMetadata = new ModelMetadata($class->getName(), $class->isInstantiable());
            foreach ($class->getAttributes() as $reflectionAttribute) {
                $attribute = $reflectionAttribute->newInstance();

                if ($attribute instanceof Model) {
                    $modelMetadata->setAttribute($attribute);
                    if (null !== $factory = $attribute->getFactory()) {
                        if (2 !== \count($factory)) {
                            throw new MappingException(\sprintf('Factory for %s must be an array with fully qualified classname and method name.', $class->getName()));
                        }
                        try {
                            $factoryClass = new \ReflectionClass($factory[0]);
                            $factoryMethod = $factoryClass->getMethod($factory[1]);
                        } catch (\ReflectionException $e) {
                            throw new MappingException(\sprintf('Can not create factory for "%s". %s', $class->getName(), $e->getMessage()));
                        }

                        $modelMetadata->addFactory($this->createFactoryMetadata($factoryClass, $factoryMethod, \PHP_INT_MAX));
                    }
                    continue;
                }
            }

            if ($constructor = $class->getConstructor()) {
                foreach ($constructor->getParameters() as $reflectionParameter) {
                    if ($propertyMetadata = $this->createPropertyMetadata($reflectionParameter, $class, $constructor)) {
                        $modelMetadata->addConstructorArgument($propertyMetadata);
                    }
                }
            }
        }

        if (!$modelMetadata->hasAttribute()) {
            throw new MissingMetadataException(\sprintf('The class "%s" does not have the "VOM\Model" attribute.', $class->getName()));
        }

        foreach ($class->getMethods() as $reflectionMethod) {
            if ($reflectionMethod->isConstructor()) {
                continue;
            }

            foreach ($reflectionMethod->getAttributes() as $reflectionAttribute) {
                $attribute = $reflectionAttribute->newInstance();

                if ($attribute instanceof Normalizer) {
                    $modelMetadata->addNormalizer($this->createNormalizerMetadata($class, $reflectionMethod, $attribute));
                    continue;
                }

                if ($attribute instanceof Denormalizer) {
                    $modelMetadata->addDenormalizer($this->createDenormalizerMetadata($class, $reflectionMethod, $attribute));
                    continue;
                }

                if ($attribute instanceof Factory) {
                    $modelMetadata->addFactory($this->createFactoryMetadata($class, $reflectionMethod, $attribute->getPriority()));
                    continue;
                }
            }
        }

        foreach ($class->getProperties() as $reflectionProperty) {
            if ($modelMetadata->isConstructorArgumentPromoted($reflectionProperty->getName())) {
                continue;
            }

            if ($propertyMetadata = $this->createPropertyMetadata($reflectionProperty, $class)) {
                $modelMetadata->addProperty($propertyMetadata);
            }
        }

        if ($parentClass = $class->getParentClass()) {
            $this->getMetadataFor($parentClass, $modelMetadata);
        }

        return $this->localCache[$class->getName()] = $modelMetadata;
    }

    /**
     * Validates and creates metadata for Normalizer methods.
     * Methods must be public, non-static and start with "get", "has" or "is.
     * Method return type is mixed, this will be validated during normalization.
     *
     * @throws MappingException When the normalizer method can not be used as is
     */
    private function createNormalizerMetadata(
        \ReflectionClass $reflectionClass,
        \ReflectionMethod $reflectionMethod,
        Normalizer $normalizer,
    ): NormalizerMetadata {
        if (!$reflectionMethod->isPublic()) {
            throw new MappingException(\sprintf('Normalizer method %s::%s() must be public.', $reflectionClass->getName(), $reflectionMethod->getName()));
        }

        if ($reflectionMethod->isStatic()) {
            throw new MappingException(\sprintf('Normalizer method %s::%s() should not be static.', $reflectionClass->getName(), $reflectionMethod->getName()));
        }

        if (preg_match('/^(get|has|is|normalize)(.+)$/i', $reflectionMethod->getName(), $matches)) {
            $virtualPropertyName = lcfirst($matches[2]);
        } else {
            throw new MappingException(\sprintf('Normalizer on "%s::%s()" cannot be added. Normalizer can only be added on methods beginning with "get", "has", "is" or "normalize".', $reflectionClass->getName(), $reflectionMethod->getName()));
        }

        return new NormalizerMetadata($reflectionClass->getName(), $reflectionMethod->getName(), $virtualPropertyName, $normalizer);
    }

    /**
     * Creates and validates metadata for Denormalizer methods.
     * Methods must be public, non-static and start with "set".
     * Methods without arguments make no sense.
     *
     * @throws MappingException when the denormalizer method can not be used as is
     */
    private function createDenormalizerMetadata(
        \ReflectionClass $reflectionClass,
        \ReflectionMethod $reflectionMethod,
        Denormalizer $denormalizer,
    ): DenormalizerMetadata {
        if (!$reflectionMethod->isPublic()) {
            throw new MappingException(\sprintf('Denormalizer method %s::%s() must be public.', $reflectionClass->getName(), $reflectionMethod->getName()));
        }

        if ($reflectionMethod->isStatic()) {
            throw new MappingException(\sprintf('Denormalizer method %s::%s() should not be static.', $reflectionClass->getName(), $reflectionMethod->getName()));
        }

        if (preg_match('/^(set|denormalize)(.+)$/i', $reflectionMethod->getName(), $matches)) {
            $virtualPropertyName = lcfirst($matches[2]);
        } else {
            throw new MappingException(\sprintf('Denormalizer on "%s::%s()" cannot be added. Denormalizer can only be added on methods beginning with "set" or "denormalize".', $reflectionClass->getName(), $reflectionMethod->getName()));
        }
        $methodArguments = [];
        foreach ($reflectionMethod->getParameters() as $reflectionParameter) {
            if ($propertyMetadata = $this->createPropertyMetadata($reflectionParameter, $reflectionClass, $reflectionMethod, $denormalizer->allowNonScalarArguments())) {
                $methodArguments[$reflectionParameter->getName()] = $propertyMetadata;
            } elseif ($type = $reflectionParameter->getType()?->getName()) {
                $found = false;
                foreach ($this->denormalizerDependencies as $dependency) {
                    if ($dependency instanceof $type) {
                        $found = true;
                        $methodArguments[$reflectionParameter->getName()] = new DependencyInjectionMetadata($reflectionParameter->getName(), $dependency);
                        break;
                    }
                }

                if (!$found) {
                    throw new MappingException(\sprintf('Argument %s of type %s in denormalizer method %s::%s() can not be injected. Did you forget to configure it as a denormalizer dependency?', $reflectionParameter->getName(), $type, $reflectionClass->getName(), $reflectionMethod->getName()));
                }
            }
        }

        if (!\count($methodArguments)) {
            throw new MappingException(\sprintf('Denormalizer method %s::%s() without arguments is useless. Consider adding VOM\Argument or removing VOM\Denormalizer.', $reflectionClass->getName(), $reflectionMethod->getName()));
        }

        return new DenormalizerMetadata($reflectionClass->getName(), $reflectionMethod->getName(), $methodArguments, $virtualPropertyName);
    }

    /**
     * Creates and validates metadata for Factory methods.
     * Methods must be public static and return an instance of the respective model.
     * To allow legacy code without a strict return type defined, this is not validated here but when it is executed.
     *
     * @throws MappingException When the factory method can not be used as is
     */
    private function createFactoryMetadata(
        \ReflectionClass $reflectionClass,
        \ReflectionMethod $reflectionMethod,
        int $priority,
    ): FactoryMetadata {
        if (!$reflectionMethod->isStatic()) {
            throw new MappingException(\sprintf('Factory method "%s::%s()" must be static.', $reflectionClass->getName(), $reflectionMethod->getName()));
        }

        if (!$reflectionMethod->isPublic()) {
            throw new MappingException(\sprintf('Factory method "%s::%s()" must be public.', $reflectionClass->getName(), $reflectionMethod->getName()));
        }

        $methodArguments = [];
        foreach ($reflectionMethod->getParameters() as $reflectionParameter) {
            if ($propertyMetadata = $this->createPropertyMetadata($reflectionParameter, $reflectionClass, $reflectionMethod)) {
                $methodArguments[$reflectionParameter->getName()] = $propertyMetadata;
            }
        }

        return new FactoryMetadata($reflectionClass->getName(), $reflectionMethod->getName(), $methodArguments, $priority);
    }

    /**
     * Creates and validates metadata for a Property.
     * A Property can be an actual class property or an argument to a normalizer, denormalizer, factory or constructor.
     *
     * @throws MappingException When the VOM\Argument attribute is used on a property. {@see Argument why we trow this php-like error}
     */
    private function createPropertyMetadata(
        \ReflectionParameter|\ReflectionProperty $reflectionProperty,
        ?\ReflectionClass $reflectionClass = null,
        ?\ReflectionMethod $reflectionMethod = null,
        bool $allowNonScalarArguments = false,
    ): ?PropertyMetadata {
        $propertyAttribute = null;
        foreach ($reflectionProperty->getAttributes() as $reflectionAttribute) {
            $attribute = $reflectionAttribute->newInstance();
            if ($attribute instanceof AbstractProperty) {
                $propertyAttribute = $attribute;
                if (null === $reflectionMethod && $attribute instanceof Argument) {
                    throw new MappingException(\sprintf('Attribute "%s" cannot target property (allowed targets: parameter)', Argument::class));
                }
                continue;
            }
        }

        if (!$propertyAttribute) {
            return null;
        }

        $class = $reflectionProperty->getDeclaringClass()->getName();
        $property = $reflectionProperty->name;
        $types = $this->propertyInfoExtractor->getTypes($class, $property, [
            'reflection_class' => $reflectionClass,
            'reflection_method' => $reflectionMethod,
            'allow_non_scalar' => $allowNonScalarArguments,
        ]);

        if (null === $types) {
            throw new MissingTypeException(\sprintf('Could not determine the type of property "%s" on class "%s".', $property, $class));
        }

        if ($reflectionProperty instanceof \ReflectionProperty) {
            $propertyMetadata = new PropertyMetadata($reflectionProperty->getName(), $types, $propertyAttribute);
        } else {
            $propertyMetadata = new ArgumentMetadata($reflectionProperty->getName(), $types, $propertyAttribute, $reflectionProperty->isPromoted());
        }

        try {
            $propertyMetadata->setDefaultValue($reflectionProperty->getDefaultValue());
        } catch (\ReflectionException) {
        }

        return $propertyMetadata;
    }
}
