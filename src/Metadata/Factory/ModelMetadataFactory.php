<?php

declare(strict_types=1);

namespace Zolex\VOM\Metadata\Factory;

use Zolex\VOM\Mapping\Model;
use Zolex\VOM\Metadata\ModelMetadata;
use Zolex\VOM\Metadata\PropertyMetadata;

class ModelMetadataFactory implements ModelMetadataFactoryInterface
{
    private array $classes = [];

    public function __construct(
        private readonly PropertyMetadataFactoryInterface $propertyMetadataFactory
    ) {
    }

    public function create(string $class, ?PropertyMetadata $parentPropertyMetadata = null): ModelMetadata
    {
        $modelMetadata = new ModelMetadata($class);
        $reflectionClass = new \ReflectionClass(trim($class, '?'));
        $modelAttribute = $reflectionClass->getAttributes(Model::class);
        if (count($modelAttribute)) {
            $modelMetadata->setAttribute($modelAttribute[0]->newInstance());
        }

        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            if (!$propertyMetadata = $this->propertyMetadataFactory->create($reflectionProperty, $reflectionClass, $parentPropertyMetadata)) {
                continue;
            }
            $type = $propertyMetadata->getType();
            if (class_exists($type)) {
                $propertyModelMetadata = $this->create($type, $propertyMetadata);
                $propertyMetadata->setModelMetadata($propertyModelMetadata);
            }

            $modelMetadata->addProperty($propertyMetadata);
        }

        return $modelMetadata;
    }
}
