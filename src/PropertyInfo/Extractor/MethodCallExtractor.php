<?php

/*
 * This file is part of the VOM package.
 *
 * (c) Andreas Linden <zlx@gmx.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zolex\VOM\PropertyInfo\Extractor;

use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Type;
use Zolex\VOM\Metadata\Exception\MappingException;

class MethodCallExtractor implements PropertyTypeExtractorInterface
{
    public function __construct()
    {
    }

    public function getTypes(string $class, string $property, array $context = []): ?array
    {
        if (!isset($context['reflection_class']) || !$context['reflection_class'] instanceof \ReflectionClass
            || !isset($context['reflection_method']) || !$context['reflection_method'] instanceof \ReflectionMethod) {
            return null;
        }

        foreach ($context['reflection_method']->getParameters() as $parameter) {
            if ($parameter->getName() === $property) {
                return $this->extractFromReflectionType(
                    $context['reflection_class']?->getName() ?? null,
                    $context['reflection_method']->getName(),
                    $parameter->getType()
                );
            }
        }

        return null;
    }

    private function extractFromReflectionType(?string $className, string $methodName, \ReflectionType $reflectionType): array
    {
        $types = [];
        $nullable = $reflectionType->allowsNull();

        foreach (($reflectionType instanceof \ReflectionUnionType || $reflectionType instanceof \ReflectionIntersectionType) ? $reflectionType->getTypes() : [$reflectionType] as $type) {
            if (!$type->isBuiltin()) {
                throw new MappingException(sprintf('Only builtin types are supported for method call %s::%s().', $className, $methodName));
            }

            $phpTypeOrClass = $type->getName();
            if ('null' === $phpTypeOrClass || 'mixed' === $phpTypeOrClass || 'never' === $phpTypeOrClass) {
                continue;
            }

            if (Type::BUILTIN_TYPE_ARRAY === $phpTypeOrClass || Type::BUILTIN_TYPE_OBJECT === $phpTypeOrClass) {
                throw new MappingException(sprintf('Only scalars are supported for method call %s::%s().', $className, $methodName));
            }

            $types[] = new Type($phpTypeOrClass, $nullable);
        }

        return $types;
    }
}
