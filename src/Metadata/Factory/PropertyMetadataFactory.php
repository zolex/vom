<?php

declare(strict_types=1);

namespace Zolex\VOM\Metadata\Factory;

use phpDocumentor\Reflection\DocBlockFactory;
use Symfony\Component\Serializer\Annotation\Groups;
use Zolex\VOM\Mapping\Property;
use Zolex\VOM\Metadata\Factory\Exception\RuntimeException;
use Zolex\VOM\Metadata\PropertyMetadata;

class PropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    public const THROW_EXCEPTIONS = 'throw_exceptions';

    private DocBlockFactory $docBlockFactory;

    public function __construct(private readonly array $options = [
        self::THROW_EXCEPTIONS => true,
    ])
    {
        $this->docBlockFactory = DocBlockFactory::createInstance();
    }

    public function create(
        \ReflectionProperty $reflectionProperty,
        \ReflectionClass $reflectionClass,
    ): ?PropertyMetadata {
        $name = $reflectionProperty->getName();
        $type = $originalType = (string) $reflectionProperty->getType();
        $propertyAttribute = $reflectionProperty->getAttributes(Property::class);
        if (!count($propertyAttribute)) {
            return null;
        }

        /* @var Property $attribute */
        $attribute = $propertyAttribute[0]->newInstance();
        if ('array' === $type) {
            if (!$type = $this->getTypeFromDocBlock($reflectionProperty, $reflectionClass->getNamespaceName())) {
                if ($this->options[self::THROW_EXCEPTIONS]) {
                    throw new RuntimeException(sprintf('Array property "%s::$%s" must define a type in it\'s docblock.', $reflectionClass->getName(), $name));
                }

                return null;
            }
        }

        $groupsAttribute = $reflectionProperty->getAttributes(Groups::class);
        if (count($groupsAttribute)) {
            /* @var Groups $groups */
            $groups = $groupsAttribute[0]->newInstance();
        } else {
            $groups = null;
        }

        return new PropertyMetadata($name, $originalType, $attribute, $groups, [], $type);
    }

    private function getTypeFromDocBlock(\ReflectionProperty $reflectionProperty, string $namespace): ?string
    {
        if (!$document = $reflectionProperty->getDocComment()) {
            return null;
        }

        $docBlock = $this->docBlockFactory->create($document);
        $types = explode('|', (string) $docBlock->getTagsByName('var')[0]?->getType());

        foreach ($types as $type) {
            if (str_ends_with($type, '[]')) {
                $type = substr($type, 0, -2);
                if (!class_exists($type)) {
                    $type = sprintf("%s%s", $namespace, $type);
                    if (!class_exists($type)) {
                        continue;
                    }
                }

                return $type;
            }
        }

        return null;
    }
}
