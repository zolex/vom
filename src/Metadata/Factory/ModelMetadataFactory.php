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
use Zolex\VOM\Mapping\Model;
use Zolex\VOM\Mapping\Property;
use Zolex\VOM\Metadata\Factory\Exception\RuntimeException;
use Zolex\VOM\Metadata\MethodCallMetadata;
use Zolex\VOM\Metadata\ModelMetadata;
use Zolex\VOM\Metadata\PropertyMetadata;

class ModelMetadataFactory implements ModelMetadataFactoryInterface
{
    /**
     * @var ModelMetadata[]
     */
    private array $localCache = [];

    /**
     * @var Type[]
     */
    private array $typesCache = [];

    public function __construct(
        private readonly PropertyInfoExtractorInterface $propertyInfoExtractor,
    ) {
    }

    public function create(string $class): ?ModelMetadata
    {
        if (\array_key_exists($class, $this->localCache)) {
            return $this->localCache[$class];
        }

        $modelMetadata = new ModelMetadata();
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
                if ($propertyMetadata = $this->createPropertyMetadata($modelMetadata, $reflectionParameter)) {
                    $modelMetadata->addConstructorArgument($propertyMetadata);
                }
            }
        }

        foreach ($reflectionClass->getMethods() as $reflectionMethod) {
            if ('__construct' === $reflectionMethod->getName()) {
                continue;
            }
            $methodArguments = [];
            foreach ($reflectionMethod->getParameters() as $reflectionParameter) {
                if ($propertyMetadata = $this->createPropertyMetadata($modelMetadata, $reflectionParameter)) {
                    if (!$reflectionMethod->isPublic()) {
                        throw new RuntimeException(sprintf('Can not use Property attributes on private method %s::%s() because VOM would not be able to call it.', $reflectionClass->getName(), $reflectionMethod->getName()));
                    }
                    $methodArguments[$reflectionParameter->getName()] = $propertyMetadata;
                }
            }

            if (\count($methodArguments)) {
                $modelMetadata->addMethodCall(new MethodCallMetadata($reflectionMethod->getName(), $methodArguments));
            }
        }

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            if ($propertyMetadata = $this->createPropertyMetadata($modelMetadata, $reflectionProperty)) {
                $modelMetadata->addProperty($propertyMetadata);
            }
        }

        return $modelMetadata;
    }

    private function createPropertyMetadata(ModelMetadata $modelMetadata, \ReflectionParameter|\ReflectionProperty $reflectionProperty): ?PropertyMetadata
    {
        $groups = [];
        $contextAttribute = null;
        $propertyAttribute = null;
        foreach ($this->loadAttributes($reflectionProperty) as $attribute) {
            if ($attribute instanceof Property) {
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

        $types = $this->getTypes($reflectionProperty->getDeclaringClass()->name, $reflectionProperty->name);
        $propertyMetadata = new PropertyMetadata($reflectionProperty->name, $types ?? [], $propertyAttribute, $groups, $contextAttribute);
        try {
            $propertyMetadata->setDefaultValue($reflectionProperty->getDefaultValue());
        } catch (\Throwable) {
        }

        $type = null;
        if ($propertyMetadata->isCollection()) {
            $type = $propertyMetadata->getCollectionType();
        } elseif ($className = $propertyMetadata->getType()) {
            $type = $className;
        }

        if (null !== $type) {
            $propertyModelMetadata = $this->create($type);
            $propertyMetadata->setModelMetadata($propertyModelMetadata);
        }

        return $propertyMetadata;
    }

    private function loadAttributes(\ReflectionMethod|\ReflectionClass|\ReflectionProperty|\ReflectionParameter $reflector): iterable
    {
        foreach ($reflector->getAttributes() as $attribute) {
            yield $attribute->newInstance();
        }
    }

    private function getTypes(string $class, string $property): ?iterable
    {
        if (null === $this->propertyInfoExtractor) {
            return null;
        }

        $key = $class.'::'.$property;
        if (isset($this->typesCache[$key])) {
            return false === $this->typesCache[$key] ? null : $this->typesCache[$key];
        }

        if (null !== $types = $this->propertyInfoExtractor->getTypes($class, $property)) {
            return $this->typesCache[$key] = $types;
        }

        $this->typesCache[$key] = false;

        return null;
    }
}
