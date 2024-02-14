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
use Zolex\VOM\Mapping\Model;
use Zolex\VOM\Mapping\Normalizer;
use Zolex\VOM\Metadata\DenormalizerMetadata;
use Zolex\VOM\Metadata\Exception\MappingException;
use Zolex\VOM\Metadata\ModelMetadata;
use Zolex\VOM\Metadata\NormalizerMetadata;
use Zolex\VOM\Metadata\PropertyMetadata;

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
            foreach ($this->loadAttributes($class) as $attribute) {
                if ($attribute instanceof Model) {
                    $modelMetadata->setAttribute($attribute);
                    continue;
                }
            }

            if (!$modelMetadata->hasAttribute()) {
                unset($this->localCache[$class->getName()]);

                return null;
            }

            if ($constructor = $class->getConstructor()) {
                foreach ($constructor->getParameters() as $reflectionParameter) {
                    if ($propertyMetadata = $this->createPropertyMetadata($reflectionParameter, $class, $constructor)) {
                        $modelMetadata->addConstructorArgument($propertyMetadata);
                    }
                }
            }
        }

        foreach ($class->getMethods() as $reflectionMethod) {
            if ('__construct' === $reflectionMethod->getName()) {
                continue;
            }

            $normalizer = null;
            $denormalizer = null;
            foreach ($this->loadAttributes($reflectionMethod) as $attribute) {
                if ($attribute instanceof Normalizer) {
                    if (preg_match('/^(get|has|is)(.+)$/i', $reflectionMethod->getName(), $matches)) {
                        $virtualPropertyName = lcfirst($matches[2]);
                    } else {
                        throw new MappingException(sprintf('Normalizer on "%s::%s()" cannot be added. Normalizer can only be added on methods beginning with "get", "has" or "is".', $class->getName(), $reflectionMethod->getName()));
                    }
                    $normalizer = new NormalizerMetadata($reflectionMethod->getName(), $virtualPropertyName);
                    continue;
                }
                if ($attribute instanceof Denormalizer) {
                    if (preg_match('/^(set)(.+)$/i', $reflectionMethod->getName(), $matches)) {
                        $virtualPropertyName = lcfirst($matches[2]);
                    } else {
                        throw new MappingException(sprintf('Denormalizer on "%s::%s()" cannot be added. Denormalizer can only be added on methods beginning with "set".', $class->getName(), $reflectionMethod->getName()));
                    }
                    $methodArguments = [];
                    foreach ($reflectionMethod->getParameters() as $reflectionParameter) {
                        if ($propertyMetadata = $this->createPropertyMetadata($reflectionParameter, $class, $reflectionMethod)) {
                            if (!$reflectionMethod->isPublic()) {
                                throw new MappingException(sprintf('Can not use Property attributes on private method %s::%s() because VOM would not be able to call it.', $class->getName(), $reflectionMethod->getName()));
                            }
                            $methodArguments[$reflectionParameter->getName()] = $propertyMetadata;
                        }
                    }

                    if (!\count($methodArguments)) {
                        throw new MappingException(sprintf('Denormalizer method %s::%s() without arguments is useless. Consider adding VOM\Argument or removing VOM\Denormalizer.', $class->getName(), $reflectionMethod->getName()));
                    }

                    $denormalizer = new DenormalizerMetadata($reflectionMethod->getName(), $virtualPropertyName, $methodArguments);
                }
            }

            if (null !== $normalizer) {
                $modelMetadata->addNormalizer($normalizer);
            }

            if (null !== $denormalizer) {
                $modelMetadata->addDenormalizer($denormalizer);
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

    private function createPropertyMetadata(
        \ReflectionParameter|\ReflectionProperty $reflectionProperty,
        ?\ReflectionClass $reflectionClass = null,
        ?\ReflectionMethod $reflectionMethod = null,
    ): ?PropertyMetadata {
        $propertyAttribute = null;
        foreach ($this->loadAttributes($reflectionProperty) as $attribute) {
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
        } catch (\Throwable) {
        }

        return $propertyMetadata;
    }

    private function loadAttributes(\ReflectionMethod|\ReflectionClass|\ReflectionProperty|\ReflectionParameter $reflector): iterable
    {
        foreach ($reflector->getAttributes() as $attribute) {
            yield $attribute->newInstance();
        }
    }
}
