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

use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\Type\WrappingTypeInterface;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Symfony\Component\TypeInfo\TypeResolver\TypeResolverInterface;
use Zolex\VOM\Mapping\AbstractProperty;
use Zolex\VOM\Mapping\Argument;
use Zolex\VOM\Mapping\Denormalizer;
use Zolex\VOM\Mapping\Factory;
use Zolex\VOM\Mapping\Model;
use Zolex\VOM\Mapping\Normalizer;
use Zolex\VOM\Metadata\ArgumentMetadata;
use Zolex\VOM\Metadata\DenormalizerMetadata;
use Zolex\VOM\Metadata\DependencyInjectionMetadata;
use Zolex\VOM\Metadata\Exception\MappingException;
use Zolex\VOM\Metadata\Exception\MissingMetadataException;
use Zolex\VOM\Metadata\Exception\MissingTypeException;
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

    /**
     * @var object[]
     */
    private array $methodDependencies = [];

    public function __construct(
        private readonly TypeResolverInterface $typeResolver,
    ) {
    }

    public function injectDenormalizerDependency(object $service): void
    {
        $message = 'The method "injectDenormalizerDependency" will be removed. Use "injectMethodDependency" instead.';
        if (function_exists('vom_trigger_deprecation')) {
            vom_trigger_deprecation($message);
        } else {
            trigger_error($message, E_USER_DEPRECATED);
        }

        $this->injectMethodDependency($service);
    }

    public function injectMethodDependency(object $service): void
    {
        if (!\in_array($service, $this->methodDependencies)) {
            $this->methodDependencies[] = $service;
        }
    }

    /**
     * If the method was called with the same class name before,
     * the same metadata instance is returned.
     *
     * Otherwise, a new metadata instance is created.
     *
     * @throws MappingException         When invalid mapping is configured on the model class
     * @throws MissingMetadataException When the class does not exist or the VOM\Model attribute is missing
     */
    public function getMetadataFor(string|\ReflectionClass $class, ?ModelMetadata $modelMetadata = null): ?ModelMetadata
    {
        if (\is_string($class)) {
            if (\array_key_exists($class, $this->localCache)) {
                return $this->localCache[$class];
            }

            try {
                $class = new \ReflectionClass(trim($class, '?'));
            } catch (\ReflectionException $e) {
                throw new MissingMetadataException(\sprintf('Can not create Model metadata for "%s". %s', $class, $e->getMessage()));
            }
        }

        if (null === $modelMetadata) {
            $modelMetadata = new ModelMetadata($class->getName(), $class->isInstantiable());
            foreach ($class->getAttributes() as $reflectionAttribute) {
                $attribute = $reflectionAttribute->newInstance();

                if ($attribute instanceof Model) {
                    $modelMetadata->setAttribute($attribute);
                    if (null !== $factory = $attribute->getFactory()) {
                        if (2 !== \count($factory)) {
                            throw new MappingException(\sprintf('Factory for %s must be an array with fully qualified classname and method name.', $class->getName()));
                        }
                        try {
                            $factoryClass = new \ReflectionClass($factory[0]);
                            $factoryMethod = $factoryClass->getMethod($factory[1]);
                        } catch (\ReflectionException $e) {
                            throw new MappingException(\sprintf('Can not create factory for "%s". %s', $class->getName(), $e->getMessage()));
                        }

                        $modelMetadata->addFactory($this->createFactoryMetadata($factoryClass, $factoryMethod, \PHP_INT_MAX));
                    }
                    continue;
                }
            }
        }

        if (!$modelMetadata->hasAttribute()) {
            throw new MissingMetadataException(\sprintf('The class "%s" does not have the "VOM\Model" attribute.', $class->getName()));
        }

        if ($constructor = $class->getConstructor()) {
            foreach ($constructor->getParameters() as $reflectionParameter) {
                $hasPropertyMetadata = false;
                foreach ($this->createPropertyMetadata($reflectionParameter, $class, $constructor) as $propertyMetadata) {
                    $hasPropertyMetadata = true;
                    $modelMetadata->addConstructorArgument($propertyMetadata);
                }

                $reflectionType = $reflectionParameter->getType();
                if (!$hasPropertyMetadata && $reflectionType instanceof \ReflectionNamedType && ($type = $reflectionType->getName())) {
                    $dependencies = $this->addDependencyInjectionArgument($type, $reflectionParameter, $class, $constructor, []);
                    $modelMetadata->addConstructorDependency(reset($dependencies));
                }
            }
        }

        $hasSerializer = false;
        $normalizerCount = 0;
        foreach ($class->getMethods() as $reflectionMethod) {
            if ($reflectionMethod->isConstructor()) {
                continue;
            }

            foreach ($reflectionMethod->getAttributes() as $reflectionAttribute) {
                $attribute = $reflectionAttribute->newInstance();

                if ($attribute instanceof Normalizer) {
                    ++$normalizerCount;
                    $normalizer = $this->createNormalizerMetadata($class, $reflectionMethod, $attribute);
                    if ('__toString' === $normalizer->getMethod()) {
                        $hasSerializer = true;
                    }
                    $modelMetadata->addNormalizer($normalizer);
                    continue;
                }

                if ($attribute instanceof Denormalizer) {
                    $modelMetadata->addDenormalizer($this->createDenormalizerMetadata($class, $reflectionMethod, $attribute));
                    continue;
                }

                if ($attribute instanceof Factory) {
                    $modelMetadata->addFactory($this->createFactoryMetadata($class, $reflectionMethod, $attribute->getPriority()));
                    continue;
                }
            }
        }

        if ($hasSerializer && $normalizerCount > 1) {
            throw new MappingException(\sprintf('The "__toString()" method on model "%s" is configured as a normalizer. There must be no additional normalizer methods.', $class->getName()));
        }

        foreach ($class->getProperties() as $reflectionProperty) {
            foreach ($this->createPropertyMetadata($reflectionProperty, $class) as $propertyMetadata) {
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
     *
     * @throws MappingException When the normalizer method can not be used as is
     */
    private function createNormalizerMetadata(
        \ReflectionClass $reflectionClass,
        \ReflectionMethod $reflectionMethod,
        Normalizer $normalizer,
    ): NormalizerMetadata {
        if (!$reflectionMethod->isPublic()) {
            throw new MappingException(\sprintf('Normalizer method %s::%s() must be public.', $reflectionClass->getName(), $reflectionMethod->getName()));
        }

        if ($reflectionMethod->isStatic()) {
            throw new MappingException(\sprintf('Normalizer method %s::%s() should not be static.', $reflectionClass->getName(), $reflectionMethod->getName()));
        }

        if ('__toString' === $reflectionMethod->getName()) {
            $virtualPropertyName = null;
        } elseif (preg_match('/^(get|has|is|normalize)(.+)$/i', $reflectionMethod->getName(), $matches)) {
            $virtualPropertyName = lcfirst($matches[2]);
        } else {
            throw new MappingException(\sprintf('Normalizer on "%s::%s()" cannot be added. Normalizer can only be added on methods beginning with "get", "has", "is" or "normalize" or on the "__toString" method.', $reflectionClass->getName(), $reflectionMethod->getName()));
        }

        $dependencyInjectionArguments = [];
        foreach ($reflectionMethod->getParameters() as $reflectionParameter) {
            $reflectionType = $reflectionParameter->getType();
            if ($reflectionType instanceof \ReflectionNamedType && ($type = $reflectionType->getName())) {
                $dependencyInjectionArguments = $this->addDependencyInjectionArgument($type, $reflectionParameter, $reflectionClass, $reflectionMethod, $dependencyInjectionArguments);
            }
        }

        return new NormalizerMetadata($reflectionClass->getName(), $reflectionMethod->getName(), [$normalizer->getScenario() => $dependencyInjectionArguments], $normalizer, $virtualPropertyName);
    }

    /**
     * Creates and validates metadata for Denormalizer methods.
     * Methods must be public, non-static and start with "set".
     * Methods without arguments make no sense.
     *
     * @throws MappingException when the denormalizer method can not be used as is
     */
    private function createDenormalizerMetadata(
        \ReflectionClass $reflectionClass,
        \ReflectionMethod $reflectionMethod,
        Denormalizer $denormalizer,
    ): DenormalizerMetadata {
        if (!$reflectionMethod->isPublic()) {
            throw new MappingException(\sprintf('Denormalizer method %s::%s() must be public.', $reflectionClass->getName(), $reflectionMethod->getName()));
        }

        if ($reflectionMethod->isStatic()) {
            throw new MappingException(\sprintf('Denormalizer method %s::%s() should not be static.', $reflectionClass->getName(), $reflectionMethod->getName()));
        }

        if (preg_match('/^(set|denormalize)(.+)$/i', $reflectionMethod->getName(), $matches)) {
            $virtualPropertyName = lcfirst($matches[2]);
        } else {
            throw new MappingException(\sprintf('Denormalizer on "%s::%s()" cannot be added. Denormalizer can only be added on methods beginning with "set" or "denormalize".', $reflectionClass->getName(), $reflectionMethod->getName()));
        }

        $methodArguments = [];
        $dependencyInjectionArguments = [];

        foreach ($reflectionMethod->getParameters() as $reflectionParameter) {
            $hasPropertyMetadata = false;
            foreach ($this->createPropertyMetadata($reflectionParameter, $reflectionClass, $reflectionMethod, $denormalizer->allowNonScalarArguments()) as $propertyMetadata) {
                $hasPropertyMetadata = true;
                $scenario = $propertyMetadata->getScenario();
                if (!isset($methodArguments[$scenario])) {
                    $methodArguments[$scenario] = [];
                }
                $methodArguments[$scenario][$reflectionParameter->getName()] = $propertyMetadata;
            }

            $reflectionType = $reflectionParameter->getType();
            if (!$hasPropertyMetadata && $reflectionType instanceof \ReflectionNamedType && ($type = $reflectionType->getName())) {
                $dependencyInjectionArguments = $this->addDependencyInjectionArgument($type, $reflectionParameter, $reflectionClass, $reflectionMethod, $dependencyInjectionArguments);
            }
        }

        foreach ($methodArguments as &$scenarioArguments) {
            $scenarioArguments = array_merge($scenarioArguments, $dependencyInjectionArguments);
        }

        if (!\count($methodArguments)) {
            throw new MappingException(\sprintf('Denormalizer method %s::%s() without arguments is useless. Consider adding VOM\Argument or removing VOM\Denormalizer.', $reflectionClass->getName(), $reflectionMethod->getName()));
        }

        return new DenormalizerMetadata($reflectionClass->getName(), $reflectionMethod->getName(), $methodArguments, $virtualPropertyName);
    }

    /**
     * Creates and validates metadata for Factory methods.
     * Methods must be public static and return an instance of the respective model.
     * To allow legacy code without a strict return type defined, this is not validated here but when it is executed.
     *
     * @throws MappingException When the factory method can not be used as is
     */
    private function createFactoryMetadata(
        \ReflectionClass $reflectionClass,
        \ReflectionMethod $reflectionMethod,
        int $priority,
    ): FactoryMetadata {
        if (!$reflectionMethod->isStatic()) {
            throw new MappingException(\sprintf('Factory method "%s::%s()" must be static.', $reflectionClass->getName(), $reflectionMethod->getName()));
        }

        if (!$reflectionMethod->isPublic()) {
            throw new MappingException(\sprintf('Factory method "%s::%s()" must be public.', $reflectionClass->getName(), $reflectionMethod->getName()));
        }

        $methodArguments = [];
        $dependencyInjectionArguments = [];

        foreach ($reflectionMethod->getParameters() as $reflectionParameter) {
            $hasPropertyMetadata = false;
            foreach ($this->createPropertyMetadata($reflectionParameter, $reflectionClass, $reflectionMethod) as $propertyMetadata) {
                $hasPropertyMetadata = true;
                $scenario = $propertyMetadata->getScenario();
                if (!isset($methodArguments[$scenario])) {
                    $methodArguments[$scenario] = [];
                }
                $methodArguments[$scenario][$reflectionParameter->getName()] = $propertyMetadata;
            }

            $reflectionType = $reflectionParameter->getType();
            if (!$hasPropertyMetadata && $reflectionType instanceof \ReflectionNamedType && ($type = $reflectionType->getName())) {
                $dependencyInjectionArguments = $this->addDependencyInjectionArgument($type, $reflectionParameter, $reflectionClass, $reflectionMethod, $dependencyInjectionArguments);
            }
        }

        foreach ($methodArguments as &$scenarioArguments) {
            $scenarioArguments = array_merge($scenarioArguments, $dependencyInjectionArguments);
        }

        return new FactoryMetadata($reflectionClass->getName(), $reflectionMethod->getName(), $methodArguments, $priority);
    }

    /**
     * Creates and validates metadata for a Property.
     * A Property can be an actual class property or an argument to a normalizer, denormalizer, factory or constructor.
     *
     * @return \Generator<PropertyMetadata>
     *
     * @throws MappingException When the VOM\Argument attribute is used on a property. {@see Argument why we trow this php-like error}
     */
    private function createPropertyMetadata(
        \ReflectionParameter|\ReflectionProperty $reflectionProperty,
        ?\ReflectionClass $reflectionClass = null,
        ?\ReflectionMethod $reflectionMethod = null,
        bool $allowNonScalarArguments = false,
    ): \Generator {
        foreach ($reflectionProperty->getAttributes() as $reflectionAttribute) {
            $attribute = $reflectionAttribute->newInstance();
            if (!$attribute instanceof AbstractProperty) {
                continue;
            }

            if (null === $reflectionMethod && $attribute instanceof Argument && !$reflectionProperty->isPromoted()) {
                throw new MappingException(\sprintf('Attribute "%s" cannot target property (allowed targets: parameter)', Argument::class));
            }

            $class = $reflectionProperty->getDeclaringClass()->getName();
            $property = $reflectionProperty->name;

            // Resolve type using Symfony TypeInfo
            try {
                $resolvedType = $this->typeResolver->resolve($reflectionProperty);
            } catch (\Throwable) {
                $resolvedType = null;
            }

            if (null === $resolvedType) {
                throw new MissingTypeException(\sprintf('Could not determine the type of property "%s" on class "%s".', $property, $class));
            }

            // Validate argument types for method calls (denormalizers, factories, etc.)
            if ($reflectionProperty instanceof \ReflectionParameter && null !== $reflectionMethod) {
                $this->validateArgumentType($resolvedType, $reflectionClass, $reflectionMethod, $allowNonScalarArguments);
            }

            if ($reflectionProperty instanceof \ReflectionProperty) {
                $propertyMetadata = new PropertyMetadata($reflectionProperty->getName(), $resolvedType, $attribute);
            } else {
                $propertyMetadata = new ArgumentMetadata($reflectionProperty->getName(), $resolvedType, $attribute);
            }

            try {
                // avoid deprecation notice for getDefaultValue from PHP 8.5
                if (!method_exists($reflectionProperty, 'hasDefaultValue') || $reflectionProperty->hasDefaultValue()) {
                    $propertyMetadata->setDefaultValue($reflectionProperty->getDefaultValue());
                }
            } catch (\ReflectionException) {
            }

            yield $propertyMetadata;
        }
    }

    /**
     * Validates that the argument type is allowed for the method call.
     *
     * @throws MappingException when the type is not supported
     */
    private function validateArgumentType(
        Type $type,
        \ReflectionClass $reflectionClass,
        \ReflectionMethod $reflectionMethod,
        bool $allowNonScalarArguments,
    ): void {
        $className = $reflectionClass->getName();
        $methodName = $reflectionMethod->getName();

        // Unwrap to get the base type
        $t = $type;
        while ($t instanceof WrappingTypeInterface) {
            $t = $t->getWrappedType();
        }

        // Check if it's an object type (class)
        if ($t instanceof ObjectType && !enum_exists($t->getClassName())) {
            throw new MappingException(\sprintf('Only builtin types are supported for method call %s::%s().', $className, $methodName));
        }

        // Check if non-scalar types are allowed
        if (!$allowNonScalarArguments && $t instanceof BuiltinType) {
            $typeId = $t->getTypeIdentifier();
            if (TypeIdentifier::ARRAY === $typeId || TypeIdentifier::OBJECT === $typeId) {
                throw new MappingException(\sprintf('Only scalars are allowed for method call %s::%s(). Consider using collection attributes.', $className, $methodName));
            }
        }
    }

    /**
     * Adds method dependency metadata to the list of dependency arguments.
     *
     * @throws MappingException when the dependency is not registered
     */
    protected function addDependencyInjectionArgument(
        string $type,
        \ReflectionParameter $reflectionParameter,
        \ReflectionClass $reflectionClass,
        \ReflectionMethod $reflectionMethod,
        array $dependencyInjectionArguments,
    ): array {

        $message = 'Custom dependency injection (method dependencies) is deprecated and will be removed in VOM 3.0 due to architectural limitations and design concerns. Please migrate to the recommended configuration and integration mechanisms provided by VOM.';
        if (function_exists('vom_trigger_deprecation')) {
            vom_trigger_deprecation($message);
        } else {
            trigger_error($message, E_USER_DEPRECATED);
        }

        $found = false;
        foreach ($this->methodDependencies as $dependency) {
            if ($dependency instanceof $type) {
                $found = true;
                $dependencyInjectionArguments[$reflectionParameter->getName()] = new DependencyInjectionMetadata($reflectionParameter->getName(), $dependency);
                break;
            }
        }

        if (!$found) {
            throw new MappingException(\sprintf('Argument %s of type %s in method %s::%s() can not be injected. Did you forget to configure it as a method dependency?', $reflectionParameter->getName(), $type, $reflectionClass->getName(), $reflectionMethod->getName()));
        }

        return $dependencyInjectionArguments;
    }
}
