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

namespace Zolex\VOM\PropertyInfo\Extractor;

use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Type;
use Zolex\VOM\Exception\InvalidArgumentException;
use Zolex\VOM\Metadata\Exception\MappingException;

class MethodCallExtractor implements PropertyTypeExtractorInterface
{
    public function __construct()
    {
    }

    /**
     * @experimental Symfony 7.1 calls this without verifying that it actually exists.
     *   the new type-info component is still experimental and the getType method is only hinted with a @method annotation
     *   Using the type-info component breaks backwards compatibility to symfony 7.0 and earlier.
     */
    public function getType(string $class, string $property, array $context = []): ?\Symfony\Component\TypeInfo\Type
    {
        return null;
    }

    public function getTypes(string $class, string $property, array $context = []): ?array
    {
        if (!isset($context['reflection_class']) || !$context['reflection_class'] instanceof \ReflectionClass
            || !isset($context['reflection_method']) || !$context['reflection_method'] instanceof \ReflectionMethod) {
            return null;
        }

        if ($context['reflection_class']->getName() !== $class) {
            throw new InvalidArgumentException(\sprintf('Reflection class in context "%s" does not match the given classname "%s".', $context['reflection_class']->getName(), $class));
        }

        foreach ($context['reflection_method']->getParameters() as $parameter) {
            if ($parameter->getName() === $property) {
                return $this->extractFromReflectionType(
                    $context['reflection_class']?->getName() ?? null,
                    $context['reflection_method']->getName(),
                    $parameter->getType(),
                    (bool) ($context['allow_non_scalar'] ?? false),
                );
            }
        }

        return null;
    }

    private function extractFromReflectionType(?string $className, string $methodName, \ReflectionType $reflectionType, bool $allowNonScalar = false): array
    {
        $types = [];
        $nullable = $reflectionType->allowsNull();

        foreach (($reflectionType instanceof \ReflectionUnionType || $reflectionType instanceof \ReflectionIntersectionType) ? $reflectionType->getTypes() : [$reflectionType] as $type) {
            if (!$type->isBuiltin()) {
                throw new MappingException(\sprintf('Only builtin types are supported for method call %s::%s().', $className, $methodName));
            }

            $phpTypeOrClass = $type->getName();
            if ('null' === $phpTypeOrClass || 'mixed' === $phpTypeOrClass || 'never' === $phpTypeOrClass) {
                continue;
            }

            if (!$allowNonScalar && (Type::BUILTIN_TYPE_ARRAY === $phpTypeOrClass || Type::BUILTIN_TYPE_OBJECT === $phpTypeOrClass)) {
                throw new MappingException(\sprintf('Only scalars are allowed for method call %s::%s(). Consider using collection attributes.', $className, $methodName));
            }

            $types[] = new Type($phpTypeOrClass, $nullable);
        }

        return $types;
    }
}
