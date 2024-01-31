<?php

declare(strict_types=1);

namespace Zolex\VOM\Metadata\Factory;

use Zolex\VOM\Mapping\Model;
use Zolex\VOM\Metadata\ModelMetadata;
use Zolex\VOM\Metadata\PropertyMetadata;
use Zolex\VOM\Test\Fixtures\PropertyMetadataFactoryInterface;

class ModelMetadataFactory implements ModelMetadataFactoryInterface
{
    /**
     * @var ModelMetadata[]
     */
    private array $localCache = [];

    public function __construct(
        private readonly PropertyMetadataFactoryInterface $propertyMetadataFactory
    ) {
    }

    public function create(string $class, ?PropertyMetadata $parentPropertyMetadata = null): ?ModelMetadata
    {
        if (\array_key_exists($class, $this->localCache)) {
            return $this->localCache[$class];
        }

        $modelMetadata = new ModelMetadata($class);
        $this->localCache[$class] = &$modelMetadata;

        $reflectionClass = new \ReflectionClass(trim($class, '?'));
        $modelAttribute = $reflectionClass->getAttributes(Model::class);
        if (count($modelAttribute)) {
            $modelMetadata->setAttribute($modelAttribute[0]->newInstance());
        } else {
            return null;
        }

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            if (!$propertyMetadata = $this->propertyMetadataFactory->create($reflectionProperty, $reflectionClass, $parentPropertyMetadata)) {
                continue;
            }
            $type = $propertyMetadata->getType();
            if ('array' === $type) {
                $propertyModelMetadata = $this->create($propertyMetadata->getArrayType());
                $propertyMetadata->setModelMetadata($propertyModelMetadata);
            } elseif (class_exists($type)) {
                $propertyModelMetadata = $this->create($type, $propertyMetadata);
                $propertyMetadata->setModelMetadata($propertyModelMetadata);
            }

            $modelMetadata->addProperty($propertyMetadata);
        }

        return $modelMetadata;
    }
}
