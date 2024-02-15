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
use Zolex\VOM\Metadata\DenormalizerMetadata;
use Zolex\VOM\Metadata\Exception\MappingException;
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

    public function __construct(
        private readonly PropertyInfoExtractorInterface $propertyInfoExtractor,
    ) {
    }

    /**
     * If the method was called with the same class name before,
     * the same metadata instance is returned.
     *
     * Otherwise, a new metadata instance is created.
     */
    public function getMetadataFor(string|\ReflectionClass $class, ?ModelMetadata $modelMetadata = null): ?ModelMetadata
    {
        if (\is_string($class)) {
            if (\array_key_exists($class, $this->localCache)) {
                return $this->localCache[$class];
            }

            try {
                $class = new \ReflectionClass(trim($class, '?'));
            } catch (\ReflectionException) {
                return null;
            }
        }

        if (null === $modelMetadata) {
            $modelMetadata = new ModelMetadata($class->getName(), $class->isInstantiable());
            foreach ($class->getAttributes() as $reflectionAttribute) {
                $attribute = $reflectionAttribute->newInstance();

                if ($attribute instanceof Model) {
                    $modelMetadata->setAttribute($attribute);
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
            return null;
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
                    $modelMetadata->addFactory($this->createFactoryMetadata($class, $reflectionMethod, $attribute));
                    continue;
                }
            }
        }

        foreach ($class->getProperties() as $reflectionProperty) {
            if ($modelMetadata->hasConstructorArgument($reflectionProperty->getName())) {
                continue;
            }

            if ($propertyMetadata = $this->createPropertyMetadata($reflectionProperty)) {
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
     */
    private function createNormalizerMetadata(
        \ReflectionClass $reflectionClass,
        \ReflectionMethod $reflectionMethod,
        Normalizer $normalizer,
    ): NormalizerMetadata {
        if (!$reflectionMethod->isPublic()) {
            throw new MappingException(sprintf('Normalizer method %s::%s() must be public.', $reflectionClass->getName(), $reflectionMethod->getName()));
        }

        if ($reflectionMethod->isStatic()) {
            throw new MappingException(sprintf('Normalizer method %s::%s() should not be static.', $reflectionClass->getName(), $reflectionMethod->getName()));
        }

        if (preg_match('/^(get|has|is)(.+)$/i', $reflectionMethod->getName(), $matches)) {
            $virtualPropertyName = lcfirst($matches[2]);
        } else {
            throw new MappingException(sprintf('Normalizer on "%s::%s()" cannot be added. Normalizer can only be added on methods beginning with "get", "has" or "is".', $reflectionClass->getName(), $reflectionMethod->getName()));
        }

        return new NormalizerMetadata($reflectionMethod->getName(), $virtualPropertyName, $normalizer->getAccessor());
    }

    /**
     * Creates and validates metadata for Denormalizer methods.
     * Methods must be public, non-static and start with "set".
     * Methods without arguments make no sense.
     */
    private function createDenormalizerMetadata(
        \ReflectionClass $reflectionClass,
        \ReflectionMethod $reflectionMethod,
        Denormalizer $denormalizer,
    ): DenormalizerMetadata {
        if (!$reflectionMethod->isPublic()) {
            throw new MappingException(sprintf('Denormalizer method %s::%s() must be public.', $reflectionClass->getName(), $reflectionMethod->getName()));
        }

        if ($reflectionMethod->isStatic()) {
            throw new MappingException(sprintf('Denormalizer method %s::%s() should not be static.', $reflectionClass->getName(), $reflectionMethod->getName()));
        }

        if (preg_match('/^(set)(.+)$/i', $reflectionMethod->getName(), $matches)) {
            $virtualPropertyName = lcfirst($matches[2]);
        } else {
            throw new MappingException(sprintf('Denormalizer on "%s::%s()" cannot be added. Denormalizer can only be added on methods beginning with "set".', $reflectionClass->getName(), $reflectionMethod->getName()));
        }
        $methodArguments = [];
        foreach ($reflectionMethod->getParameters() as $reflectionParameter) {
            if ($propertyMetadata = $this->createPropertyMetadata($reflectionParameter, $reflectionClass, $reflectionMethod)) {
                $methodArguments[$reflectionParameter->getName()] = $propertyMetadata;
            }
        }

        if (!\count($methodArguments)) {
            throw new MappingException(sprintf('Denormalizer method %s::%s() without arguments is useless. Consider adding VOM\Argument or removing VOM\Denormalizer.', $reflectionClass->getName(), $reflectionMethod->getName()));
        }

        return new DenormalizerMetadata($reflectionMethod->getName(), $methodArguments, $virtualPropertyName);
    }

    /**
     * Creates and validates metadata for Factory methods.
     * Methods must be public static and return an instance of the respective model.
     * To allow legacy code without a strict return type defined, this is not validated here but when it is executed.
     */
    private function createFactoryMetadata(
        \ReflectionClass $reflectionClass,
        \ReflectionMethod $reflectionMethod,
        Factory $factory,
    ): FactoryMetadata {
        if (!$reflectionMethod->isStatic()) {
            throw new MappingException(sprintf('Factory method %s::%s() must be static.', $reflectionClass->getName(), $reflectionMethod->getName()));
        }

        if (!$reflectionMethod->isPublic()) {
            throw new MappingException(sprintf('Factory method %s::%s() must be public.', $reflectionClass->getName(), $reflectionMethod->getName()));
        }

        $methodArguments = [];
        foreach ($reflectionMethod->getParameters() as $reflectionParameter) {
            if ($propertyMetadata = $this->createPropertyMetadata($reflectionParameter, $reflectionClass, $reflectionMethod)) {
                $methodArguments[$reflectionParameter->getName()] = $propertyMetadata;
            }
        }

        return new FactoryMetadata($reflectionMethod->getName(), $methodArguments, $factory->getPriority());
    }

    /**
     * Creates and validates metadata for a Property.
     * A Property can be an actual class property or an argument to a normalizer, denormalizer, factory or constructor.
     */
    private function createPropertyMetadata(
        \ReflectionParameter|\ReflectionProperty $reflectionProperty,
        ?\ReflectionClass $reflectionClass = null,
        ?\ReflectionMethod $reflectionMethod = null,
    ): ?PropertyMetadata {
        $propertyAttribute = null;
        foreach ($reflectionProperty->getAttributes() as $reflectionAttribute) {
            $attribute = $reflectionAttribute->newInstance();
            if ($attribute instanceof AbstractProperty) {
                /* @see Argument why we trow this php-like error */
                if (null === $reflectionMethod && $attribute instanceof Argument) {
                    throw new MappingException(sprintf('Attribute "%s" cannot target property (allowed targets: parameter)', Argument::class));
                }
                $propertyAttribute = $attribute;
                continue;
            }
        }

        if (!$propertyAttribute) {
            return null;
        }

        $class = $reflectionProperty->getDeclaringClass()->name;
        $property = $reflectionProperty->name;
        $types = $this->propertyInfoExtractor->getTypes($class, $property, [
            'reflection_class' => $reflectionClass,
            'reflection_method' => $reflectionMethod,
        ]);

        $propertyMetadata = new PropertyMetadata($reflectionProperty->name, $types, $propertyAttribute);
        try {
            $propertyMetadata->setDefaultValue($reflectionProperty->getDefaultValue());
        } catch (\ReflectionException) {
        }

        return $propertyMetadata;
    }
}
