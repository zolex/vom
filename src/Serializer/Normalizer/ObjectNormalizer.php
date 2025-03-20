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

namespace Zolex\VOM\Serializer\Normalizer;

use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException as PropertyAccessInvalidArgumentException;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UninitializedPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\BadMethodCallException;
use Symfony\Component\Serializer\Exception\CircularReferenceException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Exception\ExtraAttributesException;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Exception\MissingConstructorArgumentsException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorResolverInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectToPopulateTrait;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;
use Zolex\VOM\Metadata\ArgumentMetadata;
use Zolex\VOM\Metadata\DependencyInjectionMetadata;
use Zolex\VOM\Metadata\Exception\FactoryException;
use Zolex\VOM\Metadata\Exception\MappingException;
use Zolex\VOM\Metadata\Exception\MissingMetadataException;
use Zolex\VOM\Metadata\Factory\ModelMetadataFactoryInterface;
use Zolex\VOM\Metadata\PropertyMetadata;
use Zolex\VOM\Test\Serializer\Normalizer\Exception\IgnoreCircularReferenceException;

/**
 * Normalizes and denormalizes VOM models and their attributes.
 */
final class ObjectNormalizer extends AbstractNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    use ObjectToPopulateTrait;
    use SerializerAwareTrait;

    public const TRUE_VALUES = [true, 1, '1', 'TRUE', 'true', 'T', 't', 'ON', 'on', 'YES', 'yes', 'Y', 'y'];
    public const FALSE_VALUES = [false, 0, '0', 'FALSE', 'false', 'F', 'f', 'OFF', 'off', 'NO', 'no', 'N', 'n'];

    /**
     * Context key  where the normalizer stores the initial/root data
     * that it was called with to allow denormalizing data from the root
     * anywhere in a potentially nested structure.
     */
    public const ROOT_DATA = 'vom_root_data';

    /**
     * Flag to control whether fields with the value `null` should be output
     * when normalizing or omitted.
     */
    public const SKIP_NULL_VALUES = 'skip_null_values';

    /**
     * Flag to control whether uninitialized PHP>=7.4 typed class properties
     * should be excluded when normalizing.
     */
    public const SKIP_UNINITIALIZED_VALUES = 'skip_uninitialized_values';

    /**
     * Flag to control whether circular references should be excluded
     * if they were not handled using a circular reference handler.
     */
    public const SKIP_CIRCULAR_REFERENCE = 'skip_circular_reference';

    private readonly \Closure $objectClassResolver;

    public function __construct(
        private readonly ModelMetadataFactoryInterface $modelMetadataFactory,
        private readonly PropertyAccessorInterface $propertyAccessor,
        ClassMetadataFactoryInterface $classMetadataFactory,
        private readonly ClassDiscriminatorResolverInterface $classDiscriminatorResolver,
        array $defaultContext = [],
    ) {
        parent::__construct($classMetadataFactory, null, $defaultContext);
        $this->objectClassResolver = ($objectClassResolver ?? 'get_class')(...);
    }

    /**
     * Returns the types potentially supported by this denormalizer.
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            'object' => true,
            '*' => true,
        ];
    }

    /**
     * Checks whether the given class is supported for denormalization by this normalizer.
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        if (!isset($context['vom']) || !$context['vom']) {
            return false;
        }

        try {
            $this->modelMetadataFactory->getMetadataFor($type);
        } catch (MissingMetadataException) {
            return false;
        }

        return \is_array($data) || \is_object($data) || \is_string($data);
    }

    /**
     * Denormalizes data back into an object of the given class.
     *
     * @throws BadMethodCallException When a denormalizer method was configured and could not be called
     * @throws MappingException       When the mapping is not configured properly, but it could not be detected earlier
     * @throws ExceptionInterface     For any other type of exception
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (null === $data) {
            return null;
        }

        $context[self::ROOT_DATA] ??= $data;

        $model = $this->createInstance($data, $type, $context, $format);
        $metadata = $this->modelMetadataFactory->getMetadataFor($model::class);
        $allowedAttributes = $this->getAllowedAttributes($type, $context, true);

        if (\is_string($data) && ($extractor = $metadata->getAttribute()?->getExtractor())) {
            if (!preg_match($extractor, $data, $matches)) {
                throw new MappingException(\sprintf('Extractor "%s" on model "%s" does not match the data "%s"', $extractor, $type, $data));
            }

            $data = $matches;
        }

        foreach ($metadata->getDenormalizers() as $denormalizer) {
            $attribute = $denormalizer->getPropertyName();
            if ($allowedAttributes && !\in_array($attribute, $allowedAttributes)) {
                continue;
            }

            $context = $this->getAttributeDenormalizationContext($type, $attribute, $context);
            $methodArguments = [];
            foreach ($denormalizer->getArguments() as $property) {
                if ($property instanceof ArgumentMetadata) {
                    $methodArguments[$property->getName()] = $this->denormalizeProperty($type, $data, $property, $format, $context);
                } elseif ($property instanceof DependencyInjectionMetadata) {
                    $methodArguments[$property->getName()] = $property->getValue();
                }
            }

            if ($model::class !== $denormalizer->getClass()) {
                throw new MappingException(\sprintf('Model class "%s" does not match the expected denormalizer class "%s".', $model::class, $denormalizer->getClass()));
            }

            try {
                $model->{$denormalizer->getMethod()}(...$methodArguments);
            } catch (\Throwable $e) {
                throw new BadMethodCallException(\sprintf('Bad denormalizer method call: %s', $e->getMessage()), 0, $e);
            }
        }

        foreach ($metadata->getProperties() as $property) {
            if ($allowedAttributes && !\in_array($property->getName(), $allowedAttributes)) {
                continue;
            }

            $context = $this->getAttributeDenormalizationContext($type, $property->getName(), $context);
            $value = $this->denormalizeProperty($type, $data, $property, $format, $context);

            if (null === $value && !$property->isNullable()) {
                continue;
            }

            try {
                $this->propertyAccessor->setValue($model, $property->getName(), $value);
            } catch (NoSuchPropertyException) {
                // this may happen for private properties without mutator
                // for example doctrine entity ID, so just ignore this case.
            } catch (PropertyAccessInvalidArgumentException $e) {
                if (preg_match('/^Expected argument of type "([^"]+)", "array" given at property path "([^"]+)".$/', $e->getMessage(), $matches)) {
                    if (\ArrayAccess::class === $matches[1] || (($implements = class_implements($matches[1])) && \in_array(\ArrayAccess::class, $implements))) {
                        throw new MappingException(\sprintf('The property "%s::$%s" seems to implement ArrayAccess. To allow VOM denormalizing it, create adder/remover methods or a mutator method accepting an array.', $metadata->getClass(), $matches[2]));
                    }
                }

                throw $e;
            }
        }

        return $model;
    }

    /**
     * Tries to instantiate a model of the given class. If applicable the given data will be injected.
     * First try to instantiate it normally while nd injecting constructor arguments.
     * If that was not possible, iterate ove the model's factories until the first one succeeds.
     *
     * Finally, fail if no instance could be created.
     *
     * @throws NotNormalizableValueException
     * @throws FactoryException              When at least one factory method is configured but failed to instantiate the model
     * @throws ExceptionInterface            For any other type of exception
     */
    protected function createInstance(array|string &$data, string $class, array &$context, ?string $format): object
    {
        if (null !== $object = $this->extractObjectToPopulate($class, $context, self::OBJECT_TO_POPULATE)) {
            return $object;
        } elseif (!$mapping = $this->classDiscriminatorResolver?->getMappingForClass($class)) {
            $mappedClass = $class;
        } elseif (null === $type = $data[$mapping->getTypeProperty()] ?? null) {
            throw NotNormalizableValueException::createForUnexpectedDataType(\sprintf('Type property "%s" not found for the abstract object "%s".', $mapping->getTypeProperty(), $class), null, ['string'], isset($context['deserialization_path']) ? $context['deserialization_path'].'.'.$mapping->getTypeProperty() : $mapping->getTypeProperty(), false);
        } elseif (null === $mappedClass = $mapping->getClassForType($type)) {
            throw NotNormalizableValueException::createForUnexpectedDataType(\sprintf('The type "%s" is not a valid value.', $type), $type, ['string'], isset($context['deserialization_path']) ? $context['deserialization_path'].'.'.$mapping->getTypeProperty() : $mapping->getTypeProperty(), true);
        }

        if ($class !== $mappedClass) {
            return $this->createInstance($data, $mappedClass, $context, $format);
        }

        $metadata = $this->modelMetadataFactory->getMetadataFor($class);

        $hasFactory = false;
        $factoryExceptions = [];
        foreach ($metadata->getFactories() as $factory) {
            $hasFactory = true;
            try {
                $factoryArguments = [];
                foreach ($factory->getArguments() as $argument) {
                    $value = $this->denormalizeProperty($class, $data, $argument, $format, $context);
                    if (null === $value && $argument->hasDefaultValue()) {
                        $value = $argument->getDefaultValue();
                    }

                    $factoryArguments[$argument->getName()] = $value;
                }

                $callable = $factory->getCallable();
                $model = \call_user_func_array($callable, $factoryArguments);
                if ($model instanceof $class) {
                    return $model;
                }

                $factoryExceptions[$factory->getLongMethodName()] = \sprintf('The factory method "%s:%s()" must return an instance of "%s".', $factory->getClass(), $factory->getMethod(), $class);
            } catch (\Throwable $e) {
                $factoryExceptions[$factory->getLongMethodName()] = $e->getMessage();
            }
        }

        if (true === $hasFactory && \count($factoryExceptions)) {
            throw new FactoryException(\sprintf("Could not instantiate model \"%s\" using any of the factory methods (tried \"%s\").\n Factory Errors:\n - %s", $metadata->getClass(), implode('", "', array_keys($factoryExceptions)), implode("\n - ", $factoryExceptions)));
        }

        if ($metadata->isInstantiable()) {
            $constructorArguments = [];
            foreach ($metadata->getConstructorArguments() as $argument) {
                $value = $this->denormalizeProperty($class, $data, $argument, $format, $context);
                if (null === $value && $argument->hasDefaultValue()) {
                    $value = $argument->getDefaultValue();
                }

                $constructorArguments[$argument->getName()] = $value;
            }

            return new $class(...$constructorArguments);
        }

        throw new NotNormalizableValueException(\sprintf('Can not create model metadata for "%s" because is is a non-instantiable type. Consider to add at least one instantiable type.', $metadata->getClass()));
    }

    /**
     * Denormalizes a single property of VOM model.
     *
     * @throws ExceptionInterface
     */
    private function denormalizeProperty(string $type, mixed $data, PropertyMetadata $property, ?string $format = null, array $context = []): mixed
    {
        if ($property->isRoot()) {
            $data = &$context[self::ROOT_DATA];
        }

        if (\is_string($data)) {
            if ($property->isSerialized()) {
                return $data;
            }

            if ($extractor = $property->getExtractor()) {
                if (!preg_match($extractor, $data, $matches)) {
                    throw new MappingException(\sprintf('Extractor "%s" on "%s::$%s" does not match the data "%s"', $extractor, $type, $property->getName(), $data));
                }
                $value = $matches[1] ?? null;
            }
        } else {
            if ($accessor = $property->getAccessor()) {
                $value = $this->propertyAccessor->getValue($data, $accessor);
            } else {
                $value = $data;
            }
        }

        if ($property->hasMap()) {
            $value = $property->getMappedValue($value);
        }

        if (null === $value && !$property->isNullable()) {
            return null;
        }

        return $this->validateAndDenormalize($type, $property, $value, $format, $context);
    }

    /**
     * Validates the submitted data and denormalizes it.
     *
     * @throws NotNormalizableValueException
     * @throws ExtraAttributesException
     * @throws MissingConstructorArgumentsException
     * @throws LogicException
     * @throws ExceptionInterface
     */
    private function validateAndDenormalize(string $currentClass, PropertyMetadata $property, mixed $data, ?string $format, array $context): mixed
    {
        $expectedTypes = [];
        $attribute = $property->getName();
        $types = $property->getTypes();
        $isUnionType = \count($types) > 1;
        $e = null;
        $extraAttributesException = null;
        $missingConstructorArgumentsException = null;
        $isNullable = false;
        foreach ($types as $type) {
            if (null === $data && $type->isNullable()) {
                return null;
            }

            $collectionValueType = $type->isCollection() ? $type->getCollectionValueTypes()[0] ?? null : null;

            // This try-catch should cover all NotNormalizableValueException (and all return branches after the first
            // exception) so we could try denormalizing all types of a union type. If the target type is not a union
            // type, we will just re-throw the caught exception.
            // In the case of no denormalization succeeds with a union type, it will fall back to the default exception
            // with the acceptable types list.
            try {
                if (null !== $collectionValueType && Type::BUILTIN_TYPE_OBJECT === $collectionValueType->getBuiltinType()) {
                    $builtinType = Type::BUILTIN_TYPE_OBJECT;
                    $class = $collectionValueType->getClassName().'[]';

                    if (\count($collectionKeyType = $type->getCollectionKeyTypes()) > 0) {
                        $context['key_type'] = \count($collectionKeyType) > 1 ? $collectionKeyType : $collectionKeyType[0];
                    }

                    $context['value_type'] = $collectionValueType;
                } elseif ($type->isCollection() && \count($collectionValueType = $type->getCollectionValueTypes()) > 0 && Type::BUILTIN_TYPE_ARRAY === $collectionValueType[0]->getBuiltinType()) {
                    // get inner type for any nested array
                    [$innerType] = $collectionValueType;

                    // note that it will break for any other builtinType
                    $dimensions = '[]';
                    while (\count($innerType->getCollectionValueTypes()) > 0 && Type::BUILTIN_TYPE_ARRAY === $innerType->getBuiltinType()) {
                        $dimensions .= '[]';
                        [$innerType] = $innerType->getCollectionValueTypes();
                    }

                    if (null !== $innerType->getClassName()) {
                        // the builtinType is the inner one and the class is the class followed by []...[]
                        $builtinType = $innerType->getBuiltinType();
                        $class = $innerType->getClassName().$dimensions;
                    } else {
                        // default fallback (keep it as array)
                        $builtinType = $type->getBuiltinType();
                        $class = $type->getClassName();
                    }
                } else {
                    $builtinType = $type->getBuiltinType();
                    $class = $type->getClassName();
                }

                $expectedTypes[Type::BUILTIN_TYPE_OBJECT === $builtinType && $class ? $class : $builtinType] = true;

                if (Type::BUILTIN_TYPE_OBJECT === $builtinType && null !== $class) {
                    if (!$this->serializer instanceof DenormalizerInterface) {
                        throw new LogicException(\sprintf('Cannot denormalize attribute "%s" for class "%s" because injected serializer is not a denormalizer.', $attribute, $class));
                    }

                    $childContext = $this->createChildContext($context, $attribute, $format);
                    if ($this->serializer->supportsDenormalization($data, $class, $format, $childContext)) {
                        return $this->serializer->denormalize($data, $class, $format, $childContext);
                    }
                }

                if (Type::BUILTIN_TYPE_BOOL === $builtinType) {
                    if (null !== $trueValue = $property->getTrueValue()) {
                        if ($data === $trueValue) {
                            return true;
                        }
                    } elseif (\in_array($data, self::TRUE_VALUES, true)) {
                        return true;
                    }

                    if (null !== $falseValue = $property->getFalseValue()) {
                        if ($data === $falseValue) {
                            return false;
                        }
                    } elseif (\in_array($data, self::FALSE_VALUES, true)) {
                        return false;
                    }

                    if ($property->isNullable()) {
                        return null;
                    }

                    ob_start();
                    var_dump($data);
                    $strData = trim(ob_get_clean());

                    throw new NotNormalizableValueException(\sprintf('%s on attribute "%s" for class "%s" could not be normalized to a boolean and the property is not nullable. Check the VOM Property config and/or the data to be normalized.', $strData, $attribute, $currentClass));
                }

                // JSON only has a Number type corresponding to both int and float PHP types.
                // PHP's json_encode, JavaScript's JSON.stringify, Go's json.Marshal as well as most other JSON encoders convert
                // floating-point numbers like 12.0 to 12 (the decimal part is dropped when possible).
                // PHP's json_decode automatically converts Numbers without a decimal part to integers.
                // To circumvent this behavior, integers are converted to floats when denormalizing JSON based formats and when
                // a float is expected.
                if (Type::BUILTIN_TYPE_FLOAT === $builtinType && \is_int($data) && null !== $format && str_contains($format, JsonEncoder::FORMAT)) {
                    return (float) $data;
                }

                if ((Type::BUILTIN_TYPE_FALSE === $builtinType && false === $data) || (Type::BUILTIN_TYPE_TRUE === $builtinType && true === $data)) {
                    return $data;
                }

                if (('is_'.$builtinType)($data)) {
                    return $data;
                }
            } catch (NotNormalizableValueException|InvalidArgumentException $e) {
                if (!$isUnionType && !$isNullable) {
                    throw $e;
                }
            } catch (ExtraAttributesException $e) {
                if (!$isUnionType && !$isNullable) {
                    throw $e;
                }

                $extraAttributesException ??= $e;
            } catch (MissingConstructorArgumentsException $e) {
                if (!$isUnionType && !$isNullable) {
                    throw $e;
                }

                $missingConstructorArgumentsException ??= $e;
            }
        }

        if ($isNullable) {
            return null;
        }

        if ($extraAttributesException) {
            throw $extraAttributesException;
        }

        if ($missingConstructorArgumentsException) {
            throw $missingConstructorArgumentsException;
        }

        if (!$isUnionType && $e) {
            throw $e;
        }

        if ($context[AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT] ?? $this->defaultContext[AbstractObjectNormalizer::DISABLE_TYPE_ENFORCEMENT] ?? false) {
            return $data;
        }

        throw NotNormalizableValueException::createForUnexpectedDataType(\sprintf('The type of the "%s" attribute for class "%s" must be one of "%s" ("%s" given).', $attribute, $currentClass, implode('", "', array_keys($expectedTypes)), get_debug_type($data)), $data, array_keys($expectedTypes), $context['deserialization_path'] ?? $attribute);
    }

    /**
     * Checks whether the given class is supported for normalization by this normalizer.
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        if (!isset($context['vom']) || !$context['vom']) {
            return false;
        }

        if (!\is_object($data)) {
            return false;
        }

        try {
            $this->modelMetadataFactory->getMetadataFor($data::class);
        } catch (MissingMetadataException) {
            return false;
        }

        return true;
    }

    /**
     * Normalizes an object into a set of arrays/scalars.
     *
     * @throws InvalidArgumentException         When the object given is not a supported type for the normalizer
     * @throws CircularReferenceException       When the normalizer detects a circular reference when no circular reference handler can fix it
     * @throws IgnoreCircularReferenceException When the normalizer detects a circular reference, and it should be ignored according to the context
     * @throws LogicException                   When the normalizer is not called in an expected context
     * @throws BadMethodCallException           When a normalizer method was called but failed
     * @throws MappingException                 When the mapping is not configured properly, but it could not be detected earlier
     * @throws ExceptionInterface               For all the other cases of errors
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        if (!\is_object($object) || !$metadata = $this->modelMetadataFactory->getMetadataFor($object::class)) {
            return null;
        }

        $data = [];
        if (!isset($context[self::ROOT_DATA])) {
            $context[self::ROOT_DATA] = &$data;
        }

        if ($this->isCircularReference($object, $context)) {
            try {
                return $this->handleCircularReference($object, $format, $context);
            } catch (CircularReferenceException $e) {
                if ($context[self::SKIP_CIRCULAR_REFERENCE] ?? $this->defaultContext[self::SKIP_CIRCULAR_REFERENCE] ?? false) {
                    throw new IgnoreCircularReferenceException();
                }

                throw new CircularReferenceException(\sprintf('%s Consider adding "%s" or "%s" to the context.', $e->getMessage(), self::CIRCULAR_REFERENCE_HANDLER, self::SKIP_CIRCULAR_REFERENCE), $e->getCode(), $e);
            }
        }

        $allowedAttributes = $this->getAllowedAttributes($object::class, $context, true);
        foreach ($metadata->getProperties() as $property) {
            if ($allowedAttributes && !\in_array($property->getName(), $allowedAttributes)) {
                continue;
            }

            $context = $this->getAttributeNormalizationContext($object, $property->getName(), $context);
            try {
                $attributeValue = $property->getName() === $this->classDiscriminatorResolver?->getMappingForMappedObject($object)?->getTypeProperty()
                    ? $this->classDiscriminatorResolver?->getTypeForMappedObject($object)
                    : $this->propertyAccessor->getValue($object, $property->getName());
            } catch (UninitializedPropertyException|\Error $e) {
                if (($context[self::SKIP_UNINITIALIZED_VALUES] ?? $this->defaultContext[self::SKIP_UNINITIALIZED_VALUES] ?? true) && $this->isUninitializedValueError($e)) {
                    continue;
                }
                throw $e;
            }

            if (null !== $attributeValue) {
                foreach ($property->getTypes() as $type) {
                    if (Type::BUILTIN_TYPE_BOOL === $type->getBuiltinType()) {
                        if ($attributeValue === $property->getTrueValue() || \in_array($attributeValue, self::TRUE_VALUES, true)) {
                            $attributeValue = $property->getTrueValue() ?? true;
                        } elseif ($attributeValue === $property->getFalseValue() || \in_array($attributeValue, self::FALSE_VALUES, true)) {
                            $attributeValue = $property->getFalseValue() ?? false;
                        }
                    }
                }
            }

            try {
                $normalizedValue = $this->serializer->normalize($attributeValue, $format, $context);
            } catch (IgnoreCircularReferenceException) {
                continue;
            }

            if (null === $normalizedValue && ($context[self::SKIP_NULL_VALUES] ?? $this->defaultContext[self::SKIP_NULL_VALUES] ?? false)) {
                continue;
            }

            if ($property->isRoot()) {
                $target = &$context[self::ROOT_DATA];
            } else {
                $target = &$data;
            }

            if ($property->hasAccessor()) {
                try {
                    $accessor = $property->getAccessor();
                    $this->propertyAccessor->setValue($target, $accessor, $normalizedValue);
                } catch (\Throwable $e) {
                    if (preg_match('/^Cannot write property "[^"]+" to an array./', $e->getMessage())) {
                        throw new MappingException(\sprintf('Normalization is only supported with array-access syntax. Accessor "%s" on class "%s" uses object syntax and therefore can not be normalized.', $accessor, $metadata->getClass()));
                    }

                    throw $e;
                }
            } else {
                $target = array_merge($target, $normalizedValue);
            }
        }

        foreach ($metadata->getNormalizers() as $normalizer) {
            $attribute = $normalizer->getPropertyName();
            if ($allowedAttributes && !\in_array($attribute, $allowedAttributes)) {
                continue;
            }

            if ($object::class !== $normalizer->getClass()) {
                throw new MappingException(\sprintf('Model class "%s" does not match the expected normalizer class "%s".', $object::class, $normalizer->getClass()));
            }

            try {
                $normalized = $object->{$normalizer->getMethod()}();
            } catch (\Throwable $e) {
                throw new BadMethodCallException(\sprintf('Bad normalizer method call: %s', $e->getMessage()), 0, $e);
            }

            if ('__toString' === $normalizer->getMethod()) {
                return $normalized;
            } elseif (null !== $accessor = $normalizer->getAccessor()) {
                $this->propertyAccessor->setValue($data, $accessor, $normalized);
            } else {
                if (!\is_array($normalized)) {
                    throw new MappingException(\sprintf('Normalizer %s::%s() without accessor must return an array.', $metadata->getClass(), $normalizer->getMethod()));
                }
                $data = array_merge($data, $normalized);
            }
        }

        return $data;
    }

    /**
     * This error may occur when specific object normalizer implementation gets attribute value
     * by accessing a public uninitialized property or by calling a method accessing such property.
     */
    private function isUninitializedValueError(\Error|UninitializedPropertyException $e): bool
    {
        return $e instanceof UninitializedPropertyException
            || str_starts_with($e->getMessage(), 'Typed property')
            && str_ends_with($e->getMessage(), 'must not be accessed before initialization');
    }
}
