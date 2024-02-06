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
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\Groups;
use Zolex\VOM\Mapping\AbstractProperty;
use Zolex\VOM\Mapping\Argument;
use Zolex\VOM\Mapping\Denormalizer;
use Zolex\VOM\Mapping\Model;
use Zolex\VOM\Mapping\Normalizer;
use Zolex\VOM\Metadata\DenormalizerMetadata;
use Zolex\VOM\Metadata\Factory\Exception\MappingException;
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

    public function getMetadataFor(string $class): ?ModelMetadata
    {
        if (\array_key_exists($class, $this->localCache)) {
            return $this->localCache[$class];
        }

        if (!class_exists($class)) {
            return null;
        }

        $modelMetadata = new ModelMetadata($class);
        $this->localCache[$class] = &$modelMetadata;

        $reflectionClass = new \ReflectionClass(trim($class, '?'));
        foreach ($this->loadAttributes($reflectionClass) as $attribute) {
            if ($attribute instanceof Model) {
                $modelMetadata->setAttribute($attribute);
                continue;
            }

            /* TODO: use normalization and denormalization context of the model
            if ($attribute instanceof Context) {
            }
            */
        }

        if (!$modelMetadata->getAttribute()) {
            unset($this->localCache[$class]);

            return null;
        }

        if ($constructor = $reflectionClass->getConstructor()) {
            foreach ($constructor->getParameters() as $reflectionParameter) {
                if ($propertyMetadata = $this->createPropertyMetadata($reflectionParameter, $reflectionClass, $constructor)) {
                    $modelMetadata->addConstructorArgument($propertyMetadata);
                }
            }
        }

        foreach ($reflectionClass->getMethods() as $reflectionMethod) {
            if ('__construct' === $reflectionMethod->getName()) {
                continue;
            }

            $groups = [];
            $normalizer = null;
            $denormalizer = null;
            foreach ($this->loadAttributes($reflectionMethod) as $attribute) {
                if ($attribute instanceof Groups) {
                    $groups = $attribute->getGroups();
                }
                if ($attribute instanceof Normalizer) {
                    $normalizer = new NormalizerMetadata($reflectionMethod->getName());
                }
                if ($attribute instanceof Denormalizer) {
                    $methodArguments = [];
                    foreach ($reflectionMethod->getParameters() as $reflectionParameter) {
                        if ($propertyMetadata = $this->createPropertyMetadata($reflectionParameter, $reflectionClass, $reflectionMethod)) {
                            if (!$reflectionMethod->isPublic()) {
                                throw new MappingException(sprintf('Can not use Property attributes on private method %s::%s() because VOM would not be able to call it.', $reflectionClass->getName(), $reflectionMethod->getName()));
                            }
                            $methodArguments[$reflectionParameter->getName()] = $propertyMetadata;
                        }
                    }

                    if (!\count($methodArguments)) {
                        throw new MappingException(sprintf('Denormalizer method %s::%s() without arguments is useless. Consider adding VOM\Argument or removing VOM\Denormalizer.', $reflectionClass->getName(), $reflectionMethod->getName()));
                    }

                    $denormalizer = new DenormalizerMetadata($reflectionMethod->getName(), $methodArguments);
                }
            }

            if (null !== $normalizer) {
                $normalizer->setGroups($groups);
                $modelMetadata->addNormalizer($normalizer);
            }

            if (null !== $denormalizer) {
                $denormalizer->setGroups($groups);
                $modelMetadata->addDenormalizer($denormalizer);
            }
        }

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            if ($modelMetadata->hasConstructorArgument($reflectionProperty->getName())) {
                continue;
            }
            if ($propertyMetadata = $this->createPropertyMetadata($reflectionProperty)) {
                $modelMetadata->addProperty($propertyMetadata);
            }
        }

        return $modelMetadata;
    }

    private function createPropertyMetadata(
        \ReflectionParameter|\ReflectionProperty $reflectionProperty,
        ?\ReflectionClass $reflectionClass = null,
        ?\ReflectionMethod $reflectionMethod = null,
    ): ?PropertyMetadata {
        $groups = [];
        $contextAttribute = null;
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

            if ($attribute instanceof Groups) {
                $groups = $attribute->getGroups();
            }

            if ($attribute instanceof Context) {
                $contextAttribute = $attribute;
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
        [$type, $arrayAccessType] = $this->extractPropertyType($class, $property, $types);
        $propertyMetadata = new PropertyMetadata($reflectionProperty->name, $type, $arrayAccessType, $propertyAttribute, $groups, $contextAttribute);
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

    /**
     * @param Type[] $types
     */
    private function extractPropertyType(string $parentClass, string $property, iterable $types): array
    {
        $hasClass = $hasArrayAccess = $isCollection = false;
        $instantiableClass = null;
        $arrayAccessType = null;
        foreach ($types as $type) {
            if ($type->isCollection()) {
                $isCollection = true;
                [$collectionType] = $this->extractPropertyType($parentClass, $property, $type->getCollectionValueTypes());
                if ($collectionType) {
                    $hasClass = true;
                    $instantiableClass = $collectionType;
                }
            } elseif ($class = $type->getClassName()) {
                if (\in_array(\ArrayAccess::class, class_implements($class))) {
                    $hasArrayAccess = true;
                    try {
                        $reflection = new \ReflectionClass($class);
                        if ($reflection->isInstantiable()) {
                            $arrayAccessType = $class;
                            continue;
                        }
                    } catch (\ReflectionException) {
                    }
                }

                $hasClass = true;
                try {
                    $reflection = new \ReflectionClass($class);
                    if ($reflection->isInstantiable()) {
                        $instantiableClass = $class;
                    }
                } catch (\ReflectionException) {
                }
            }
        }

        if ($hasClass) {
            if (null === $instantiableClass) {
                throw new MappingException(sprintf('Could not find a class that can be instantiated for %s::$%s, found %s. Consider adding a PhpDoc Tag with an instantiable type.', $parentClass, $property, $class));
            }

            if ($hasArrayAccess && null === $arrayAccessType) {
                throw new MappingException(sprintf('Could not find an ArrayAccess that can be instantiated for %s::$%s, found %s. Consider adding a PhpDoc Tag with an instantiable ArrayAccess type like ArrayObject.', $parentClass, $property, $class));
            }

            return [$instantiableClass.($hasArrayAccess || $isCollection ? '[]' : ''), $arrayAccessType];
        }

        foreach ($types as $type) {
            if ($builtinType = $type->getBuiltinType()) {
                return [$builtinType, null];
            }
        }

        return [null, null];
    }
}
