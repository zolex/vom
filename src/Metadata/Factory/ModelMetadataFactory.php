<?php

declare(strict_types=1);

namespace Zolex\VOM\Metadata\Factory;

use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Attribute\Context;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Exception\MappingException;
use Zolex\VOM\Mapping\Model;
use Zolex\VOM\Mapping\Property;
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
        private readonly PropertyInfoExtractorInterface $propertyInfoExtractor
    ) {
    }

    public function create(string $class, ?PropertyMetadata $parentPropertyMetadata = null): ?ModelMetadata
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

            if ($attribute instanceof Context) {
                //...
            }
        }

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            $propertyAttribute = null;
            $groups = [];
            foreach ($this->loadAttributes($reflectionProperty) as $attribute) {
                if ($attribute instanceof Property) {
                    $propertyAttribute = $attribute;
                    continue;
                }
                if ($attribute instanceof Groups) {
                    $groups = $attribute->getGroups();
                }
            }

            if (!$propertyAttribute) {
                continue;
            }

            $types = $this->getTypes($reflectionProperty->getDeclaringClass()->name, $reflectionProperty->name);
            $propertyMetadata = new PropertyMetadata($reflectionProperty->name, $types, $propertyAttribute, $groups);

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

            $modelMetadata->addProperty($propertyMetadata);
        }

        return $modelMetadata;
    }

    private function loadAttributes(\ReflectionMethod|\ReflectionClass|\ReflectionProperty $reflector): iterable
    {
        foreach ($reflector->getAttributes() as $attribute) {
            try {
                yield $attribute->newInstance();
            } catch (\Error $e) {
                if (\Error::class !== $e::class) {
                    throw $e;
                }
                $on = match (true) {
                    $reflector instanceof \ReflectionClass => ' on class '.$reflector->name,
                    $reflector instanceof \ReflectionMethod => sprintf(' on "%s::%s()"', $reflector->getDeclaringClass()->name, $reflector->name),
                    $reflector instanceof \ReflectionProperty => sprintf(' on "%s::$%s"', $reflector->getDeclaringClass()->name, $reflector->name),
                    default => '',
                };

                throw new MappingException(sprintf('Could not instantiate attribute "%s"%s.', $attribute->getName(), $on), 0, $e);
            }
        }
    }

    private function getTypes(string $class, string $property): iterable
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
