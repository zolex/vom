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
use Symfony\Component\PropertyAccess\Exception\NoSuchIndexException;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UninitializedPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
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
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectToPopulateTrait;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;
use Symfony\Component\TypeInfo\Exception\LogicException as TypeInfoLogicException;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\IntersectionType;
use Symfony\Component\TypeInfo\Type\NullableType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\Type\UnionType;
use Symfony\Component\TypeInfo\Type\WrappingTypeInterface;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Zolex\VOM\Metadata\AccessorListItemMetadata;
use Zolex\VOM\Metadata\ArgumentMetadata;
use Zolex\VOM\Metadata\DependencyInjectionMetadata;
use Zolex\VOM\Metadata\Exception\FactoryException;
use Zolex\VOM\Metadata\Exception\MappingException;
use Zolex\VOM\Metadata\Exception\MissingMetadataException;
use Zolex\VOM\Metadata\Factory\ModelMetadataFactoryInterface;
use Zolex\VOM\Metadata\ModelMetadata;
use Zolex\VOM\Metadata\PropertyMetadata;
use Zolex\VOM\Serializer\Normalizer\Exception\IgnoreCircularReferenceException;

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
     * Context key  where the normalizer stores the all accessors
     * that have been used to reach the current nesting level.
     * Required for relative accessors.
     */
    public const NESTING_PATH = 'vom_nesting_path';

    /**
     * Flag to control whether fields with the value `null` should be output
     * when normalizing or omitted.
     */
    public const SKIP_NULL_VALUES = 'skip_null_values';

    /**
     * While denormalizing, we can verify that type matches.
     *
     * You can disable this by setting this flag to true.
     */
    public const DISABLE_TYPE_ENFORCEMENT = 'disable_type_enforcement';

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
        ?callable $objectClassResolver = null,
    ) {
        parent::__construct($classMetadataFactory, null, $defaultContext);
        $this->objectClassResolver = ($objectClassResolver ?? 'get_class')(...);
    }

    /**
     * Returns the types potentially supported by this denormalizer.
     *
     * @return array<class-string|'*'|'object'|string, bool|null>
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

        $scenario = $context['scenario'] ?? ModelMetadata::DEFAULT_SCENARIO;
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
            foreach ($denormalizer->getArguments($scenario) as $argument) {
                $methodArguments = $this->addMethodArgument($argument, $type, $data, $format, $context, $methodArguments);
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

        foreach ($metadata->getProperties($scenario) as $property) {
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
     * Adds an argument to the given list of method arguments. Can either be a VOM\Argument or a Dependency.
     */
    private function addMethodArgument(DependencyInjectionMetadata|ArgumentMetadata $argument, ?string $class, mixed $data, ?string $format, array $context, array $methodArguments): array
    {
        if ($argument instanceof DependencyInjectionMetadata) {
            $methodArguments[$argument->getName()] = $argument->getValue();
        } else {
            $value = $this->denormalizeProperty($class, $data, $argument, $format, $context);
            if (null === $value && $argument->hasDefaultValue()) {
                $value = $argument->getDefaultValue();
            }

            $methodArguments[$argument->getName()] = $value;
        }

        return $methodArguments;
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
    protected function createInstance(array|string|AccessorListItemMetadata &$data, string $class, array &$context, ?string $format): object
    {
        $type = null;
        if (null !== $object = $this->extractObjectToPopulate($class, $context, self::OBJECT_TO_POPULATE)) {
            return $object;
        } elseif (!$mapping = $this->classDiscriminatorResolver?->getMappingForClass($class)) {
            $mappedClass = $class;
        } elseif (\is_array($data) && (null === $type = $data[$mapping->getTypeProperty()] ?? null)) {
            throw NotNormalizableValueException::createForUnexpectedDataType(\sprintf('Type property "%s" not found for the abstract object "%s".', $mapping->getTypeProperty(), $class), null, ['string'], isset($context['deserialization_path']) ? $context['deserialization_path'].'.'.$mapping->getTypeProperty() : $mapping->getTypeProperty(), false);
        } elseif (null === $mappedClass = $mapping->getClassForType($type)) {
            throw NotNormalizableValueException::createForUnexpectedDataType(\sprintf('The type "%s" is not a valid value.', $type), $type, ['string'], isset($context['deserialization_path']) ? $context['deserialization_path'].'.'.$mapping->getTypeProperty() : $mapping->getTypeProperty(), true);
        }

        if ($class !== $mappedClass) {
            return $this->createInstance($data, $mappedClass, $context, $format);
        }

        $metadata = $this->modelMetadataFactory->getMetadataFor($class);

        $scenario = $context['scenario'] ?? ModelMetadata::DEFAULT_SCENARIO;
        $hasFactory = false;
        $factoryExceptions = [];
        foreach ($metadata->getFactories() as $factory) {
            $hasFactory = true;
            try {
                $factoryArguments = [];
                foreach ($factory->getArguments($scenario) as $argument) {
                    $factoryArguments = $this->addMethodArgument($argument, $class, $data, $format, $context, $factoryArguments);
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
            foreach ($metadata->getConstructorArguments($scenario) as $argument) {
                $constructorArguments = $this->addMethodArgument($argument, $class, $data, $format, $context, $constructorArguments);
            }

            return new $class(...$constructorArguments);
        }

        throw new NotNormalizableValueException(\sprintf('Can not create model metadata for "%s" because is is a non-instantiable type. Consider to add at least one instantiable type.', $metadata->getClass()));
    }

    private function denormalizeProperty(string $type, mixed $data, PropertyMetadata $property, ?string $format = null, array $context = []): mixed
    {
        if ($property->isRoot()) {
            $data = &$context[self::ROOT_DATA];
        }

        $value = $this->extractValue($type, $data, $property, $context);

        if ($property->hasMap()) {
            $value = $property->getMappedValue($value);
        }

        if (null === $value && !$property->isNullable()) {
            return null;
        }

        return $this->validateAndDenormalize($property, $type, $value, $format, $context);
    }

    private function extractValue(string $type, mixed $data, PropertyMetadata $property, array &$context): mixed
    {
        if (\is_string($data)) {
            return $this->extractFromString($type, $data, $property);
        }

        if (!$accessor = $property->getAccessor()) {
            return $data;
        }

        if ((null !== $relative = $property->getRelative()) && isset($context[self::NESTING_PATH])) {
            return $this->resolveRelativeAccessor($context, $data, $property, $accessor, $relative);
        }

        return $this->resolveStandardAccessor($context, $data, $property, $accessor, $type);
    }

    private function extractFromString(string $type, string $data, PropertyMetadata $property): mixed
    {
        if ($property->isSerialized()) {
            return $data;
        }

        if ($extractor = $property->getExtractor()) {
            if (!preg_match($extractor, $data, $matches)) {
                throw new MappingException(\sprintf('Extractor "%s" on "%s::$%s" does not match the data "%s"', $extractor, $type, $property->getName(), $data));
            }

            return $matches[1] ?? null;
        }

        return null;
    }

    private function resolveRelativeAccessor(array &$context, mixed $data, PropertyMetadata $property, mixed $accessor, mixed $relative): mixed
    {
        if (\is_array($relative) && \is_array($accessor)) {
            $value = [];
            foreach ($accessor as $key => $itemAccessor) {
                if (isset($relative[$key])) {
                    $path = \array_slice($context[self::NESTING_PATH], 0, -$relative[$key]);
                    $path[] = $itemAccessor;
                    $fromRoot = implode('', $path);
                    $val = $this->propertyAccessor->getValue($context[self::ROOT_DATA], $fromRoot);
                } else {
                    $val = $this->propertyAccessor->getValue($data, $itemAccessor);
                }
                $value[] = new AccessorListItemMetadata($key, $itemAccessor, $val);
            }

            return $value;
        }

        $path = \array_slice($context[self::NESTING_PATH], 0, -$relative);
        $path[] = $accessor;
        $fromRoot = implode('', $path);

        return $this->propertyAccessor->getValue($context[self::ROOT_DATA], $fromRoot);
    }

    private function resolveStandardAccessor(array &$context, mixed $data, PropertyMetadata $property, mixed $accessor, string $type): mixed
    {
        $context[self::NESTING_PATH] ??= [];
        $context[self::NESTING_PATH][] = $accessor;

        if (\is_array($accessor)) {
            $value = [];
            foreach ($accessor as $key => $itemAccessor) {
                $val = $this->propertyAccessor->getValue($data, $itemAccessor);
                $value[] = new AccessorListItemMetadata($key, $itemAccessor, $val);
            }

            return $value;
        }

        try {
            $value = $this->propertyAccessor->getValue($data, $accessor);

            if (null === $value && null !== $property->getClass() && !($context[self::SKIP_NULL_VALUES] ?? false)) {
                try {
                    $this->modelMetadataFactory->getMetadataFor($property->getClass());

                    return [];
                } catch (MissingMetadataException) {
                    // Proceed with null
                }
            }

            return $value;
        } catch (NoSuchIndexException|NoSuchPropertyException $e) {
            if ($data instanceof AccessorListItemMetadata) {
                throw new MappingException(\sprintf('Model "%s" is wrapped in "%s". Only valid accessors are "key", "value" and "accessor".', $type, AccessorListItemMetadata::class));
            }
            throw $e;
        }
    }

    /**
     * @param Type   $type
     * @param string $attribute
     *
     * @throws ExceptionInterface
     */
    private function validateAndDenormalize(PropertyMetadata $property, string $currentClass, mixed $data, ?string $format, array $context): mixed
    {
        $type = $property->getType();
        $attribute = $property->getName();

        $expectedTypes = [];

        // BC layer for type-info < 7.2
        if (method_exists(Type::class, 'asNonNullable')) {
            $isUnionType = $type->asNonNullable() instanceof UnionType;
        } else {
            $isUnionType = $type instanceof UnionType;
        }

        $e = null;
        $extraAttributesException = null;
        $missingConstructorArgumentsException = null;

        $types = match (true) {
            $type instanceof IntersectionType => throw new LogicException('Unable to handle intersection type.'),
            $type instanceof UnionType => $type->getTypes(),
            default => [$type],
        };

        foreach ($types as $t) {
            if (null === $data && $type->isNullable()) {
                return null;
            }

            $collectionKeyType = $collectionValueType = null;
            if ($t instanceof CollectionType) {
                $collectionKeyType = $t->getCollectionKeyType();
                $collectionValueType = $t->getCollectionValueType();
            }

            // BC layer for type-info < 7.2
            if (method_exists(Type::class, 'getBaseType')) {
                $t = $t->getBaseType();
            } else {
                while ($t instanceof WrappingTypeInterface) {
                    $t = $t->getWrappedType();
                }
            }

            // Fix a collection that contains the only one element
            // This is special to xml format only
            if ('xml' === $format && $collectionValueType && (!\is_array($data) || !\is_int(key($data)))) {
                // BC layer for type-info < 7.2
                $isMixedType = method_exists(Type::class, 'isA') ? $collectionValueType->isA(TypeIdentifier::MIXED) : $collectionValueType->isIdentifiedBy(TypeIdentifier::MIXED);
                if (!$isMixedType) {
                    $data = [$data];
                }
            }

            // This try-catch should cover all NotNormalizableValueException (and all return branches after the first
            // exception) so we could try denormalizing all types of an union type. If the target type is not an union
            // type, we will just re-throw the catched exception.
            // In the case of no denormalization succeeds with an union type, it will fall back to the default exception
            // with the acceptable types list.
            try {
                // In XML and CSV all basic datatypes are represented as strings, it is e.g. not possible to determine,
                // if a value is meant to be a string, float, int or a boolean value from the serialized representation.
                // That's why we have to transform the values, if one of these non-string basic datatypes is expected.
                $typeIdentifier = $t->getTypeIdentifier();
                if (\is_string($data) && (XmlEncoder::FORMAT === $format || CsvEncoder::FORMAT === $format)) {
                    if ('' === $data) {
                        if (TypeIdentifier::ARRAY === $typeIdentifier) {
                            return [];
                        }

                        if (TypeIdentifier::STRING === $typeIdentifier) {
                            return '';
                        }
                    }

                    switch ($typeIdentifier) {
                        case TypeIdentifier::BOOL:
                            // according to https://www.w3.org/TR/xmlschema-2/#boolean, valid representations are "false", "true", "0" and "1"
                            if ('false' === $data || '0' === $data) {
                                $data = false;
                            } elseif ('true' === $data || '1' === $data) {
                                $data = true;
                            } else {
                                throw NotNormalizableValueException::createForUnexpectedDataType(\sprintf('The type of the "%s" attribute for class "%s" must be bool ("%s" given).', $attribute, $currentClass, $data), $data, [Type::bool()], $context['deserialization_path'] ?? null);
                            }
                            break;
                        case TypeIdentifier::INT:
                            if (ctype_digit(isset($data[0]) && '-' === $data[0] ? substr($data, 1) : $data)) {
                                $data = (int) $data;
                            } else {
                                throw NotNormalizableValueException::createForUnexpectedDataType(\sprintf('The type of the "%s" attribute for class "%s" must be int ("%s" given).', $attribute, $currentClass, $data), $data, [Type::int()], $context['deserialization_path'] ?? null);
                            }
                            break;
                        case TypeIdentifier::FLOAT:
                            if (is_numeric($data)) {
                                return (float) $data;
                            }

                            return match ($data) {
                                'NaN' => \NAN,
                                'INF' => \INF,
                                '-INF' => -\INF,
                                default => throw NotNormalizableValueException::createForUnexpectedDataType(\sprintf('The type of the "%s" attribute for class "%s" must be float ("%s" given).', $attribute, $currentClass, $data), $data, [Type::float()], $context['deserialization_path'] ?? null),
                            };
                    }
                }

                if (is_numeric($data) && XmlEncoder::FORMAT === $format) {
                    // encoder parsed them wrong, so they might need to be transformed back
                    switch ($typeIdentifier) {
                        case TypeIdentifier::STRING:
                            return (string) $data;
                        case TypeIdentifier::FLOAT:
                            return (float) $data;
                        case TypeIdentifier::INT:
                            return (int) $data;
                    }
                }

                if ($collectionValueType) {
                    try {
                        $collectionValueBaseType = $collectionValueType;

                        // BC layer for type-info < 7.2
                        if (!interface_exists(WrappingTypeInterface::class)) {
                            $collectionValueBaseType = $collectionValueType->getBaseType();
                        } else {
                            while ($collectionValueBaseType instanceof WrappingTypeInterface) {
                                $collectionValueBaseType = $collectionValueBaseType->getWrappedType();
                            }
                        }
                    } catch (TypeInfoLogicException) {
                        $collectionValueBaseType = Type::mixed();
                    }

                    if ($collectionValueBaseType instanceof ObjectType) {
                        $typeIdentifier = TypeIdentifier::OBJECT;
                        $class = $collectionValueBaseType->getClassName().'[]';
                        $context['key_type'] = $collectionKeyType;
                        $context['value_type'] = $collectionValueType;
                    } elseif (
                        // BC layer for type-info < 7.2
                        !class_exists(NullableType::class) && TypeIdentifier::ARRAY === $collectionValueBaseType->getTypeIdentifier()
                        || $collectionValueBaseType instanceof BuiltinType && TypeIdentifier::ARRAY === $collectionValueBaseType->getTypeIdentifier()
                    ) {
                        // get inner type for any nested array
                        $innerType = $collectionValueType;
                        if ($innerType instanceof NullableType) {
                            $innerType = $innerType->getWrappedType();
                        }

                        // note that it will break for any other builtinType
                        $dimensions = '[]';
                        while ($innerType instanceof CollectionType) {
                            $dimensions .= '[]';
                            $innerType = $innerType->getCollectionValueType();
                            if ($innerType instanceof NullableType) {
                                $innerType = $innerType->getWrappedType();
                            }
                        }

                        while ($innerType instanceof WrappingTypeInterface) {
                            $innerType = $innerType->getWrappedType();
                        }

                        if ($innerType instanceof ObjectType) {
                            // the builtinType is the inner one and the class is the class followed by []...[]
                            $typeIdentifier = TypeIdentifier::OBJECT;
                            $class = $innerType->getClassName().$dimensions;
                        } else {
                            // default fallback (keep it as array)
                            if ($t instanceof ObjectType) {
                                $typeIdentifier = TypeIdentifier::OBJECT;
                                $class = $t->getClassName();
                            } else {
                                $typeIdentifier = $t->getTypeIdentifier();
                                $class = null;
                            }
                        }
                    } elseif ($t instanceof ObjectType) {
                        $typeIdentifier = TypeIdentifier::OBJECT;
                        $class = $t->getClassName();
                    } else {
                        $typeIdentifier = $t->getTypeIdentifier();
                        $class = null;
                    }
                } else {
                    if ($t instanceof ObjectType) {
                        $typeIdentifier = TypeIdentifier::OBJECT;
                        $class = $t->getClassName();
                    } else {
                        $typeIdentifier = $t->getTypeIdentifier();
                        $class = null;
                    }
                }

                $expectedTypes[TypeIdentifier::OBJECT === $typeIdentifier && $class ? $class : $typeIdentifier->value] = true;

                if (TypeIdentifier::BOOL === $typeIdentifier) {
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

                if (TypeIdentifier::OBJECT === $typeIdentifier && $t instanceof ObjectType && enum_exists($t->getClassName())) {
                    $className = $t->getClassName();
                    $refEnum = new \ReflectionEnum($className);
                    $value = \is_array($data) && 1 === \count($data) ? $data[0] : $data;
                    if ($refEnum->isBacked()) {
                        $backed = $className::tryFrom($value);
                        if (null === $backed) {
                            throw NotNormalizableValueException::createForUnexpectedDataType(\sprintf('Failed to create backed enum because the enum "%s" has no case "%s".', $className, $value), $data, ['unknown'], $context['deserialization_path'] ?? null);
                        }

                        return $backed;
                    }

                    foreach ($className::cases() as $case) {
                        if ($case->name === $value) {
                            return $case;
                        }
                    }

                    throw NotNormalizableValueException::createForUnexpectedDataType(\sprintf('Failed to create pure enum because the enum "%s" has no case "%s".', $className, $value), $data, ['unknown'], $context['deserialization_path'] ?? null);
                }

                if (TypeIdentifier::OBJECT === $typeIdentifier && null !== $class) {
                    if (!$this->serializer instanceof DenormalizerInterface) {
                        throw new LogicException(\sprintf('Cannot denormalize attribute "%s" for class "%s" because injected serializer is not a denormalizer.', $attribute, $class));
                    }

                    $childContext = $this->createChildContext($context, $attribute, $format);
                    if ($this->serializer->supportsDenormalization($data, $class, $format, $childContext)) {
                        return $this->serializer->denormalize($data, $class, $format, $childContext);
                    }
                }

                // JSON only has a Number type corresponding to both int and float PHP types.
                // PHP's json_encode, JavaScript's JSON.stringify, Go's json.Marshal as well as most other JSON encoders convert
                // floating-point numbers like 12.0 to 12 (the decimal part is dropped when possible).
                // PHP's json_decode automatically converts Numbers without a decimal part to integers.
                // To circumvent this behavior, integers are converted to floats when denormalizing JSON based formats and when
                // a float is expected.
                if (TypeIdentifier::FLOAT === $typeIdentifier && \is_int($data) && null !== $format && str_contains($format, JsonEncoder::FORMAT)) {
                    return (float) $data;
                }

                if (TypeIdentifier::BOOL === $typeIdentifier && (\is_string($data) || \is_int($data)) && ($context[self::FILTER_BOOL] ?? false)) {
                    return filter_var($data, \FILTER_VALIDATE_BOOL, \FILTER_NULL_ON_FAILURE);
                }

                $dataMatchesExpectedType = match ($typeIdentifier) {
                    TypeIdentifier::ARRAY => \is_array($data),
                    TypeIdentifier::BOOL => \is_bool($data),
                    TypeIdentifier::CALLABLE => \is_callable($data),
                    TypeIdentifier::FALSE => false === $data,
                    TypeIdentifier::FLOAT => \is_float($data),
                    TypeIdentifier::INT => \is_int($data),
                    TypeIdentifier::ITERABLE => is_iterable($data),
                    TypeIdentifier::MIXED => true,
                    TypeIdentifier::NULL => null === $data,
                    TypeIdentifier::OBJECT => \is_object($data),
                    TypeIdentifier::RESOURCE => \is_resource($data),
                    TypeIdentifier::STRING => \is_string($data),
                    TypeIdentifier::TRUE => true === $data,
                    default => false,
                };

                if ($dataMatchesExpectedType) {
                    return $data;
                }
            } catch (NotNormalizableValueException|InvalidArgumentException $e) {
                if (!$type instanceof UnionType) {
                    throw $e;
                }
            } catch (ExtraAttributesException $e) {
                if (!$type instanceof UnionType) {
                    throw $e;
                }

                $extraAttributesException ??= $e;
            } catch (MissingConstructorArgumentsException $e) {
                if (!$type instanceof UnionType) {
                    throw $e;
                }

                $missingConstructorArgumentsException ??= $e;
            }
        }

        if ('' === $data && (XmlEncoder::FORMAT === $format || CsvEncoder::FORMAT === $format) && $type->isNullable()) {
            return null;
        }

        if ($extraAttributesException) {
            throw $extraAttributesException;
        }

        if ($missingConstructorArgumentsException) {
            throw $missingConstructorArgumentsException;
        }

        // BC layer for type-info < 7.2
        if (!class_exists(NullableType::class)) {
            if (!$isUnionType && $e) {
                throw $e;
            }
        } else {
            if ($e && !($type instanceof UnionType && !$type instanceof NullableType)) {
                throw $e;
            }
        }

        if ($context[self::DISABLE_TYPE_ENFORCEMENT] ?? $this->defaultContext[self::DISABLE_TYPE_ENFORCEMENT] ?? false) {
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
    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        if (!$this->serializer instanceof NormalizerInterface) {
            throw new InvalidArgumentException('The serializer must implement the NormalizerInterface');
        }

        if (!$this->serializer instanceof DenormalizerInterface) {
            throw new InvalidArgumentException('The serializer must implement the DenormalizerInterface');
        }

        if (!\is_object($data) || !$metadata = $this->modelMetadataFactory->getMetadataFor($data::class)) {
            return null;
        }

        $scenario = $context['scenario'] ?? ModelMetadata::DEFAULT_SCENARIO;
        $normalizedData = [];
        if (!isset($context[self::ROOT_DATA])) {
            $context[self::ROOT_DATA] = &$normalizedData;
        }

        if ($this->isCircularReference($data, $context)) {
            try {
                return $this->handleCircularReference($data, $format, $context);
            } catch (CircularReferenceException $e) {
                if ($context[self::SKIP_CIRCULAR_REFERENCE] ?? $this->defaultContext[self::SKIP_CIRCULAR_REFERENCE] ?? false) {
                    throw new IgnoreCircularReferenceException();
                }

                throw new CircularReferenceException(\sprintf('%s Consider adding "%s" or "%s" to the context.', $e->getMessage(), self::CIRCULAR_REFERENCE_HANDLER, self::SKIP_CIRCULAR_REFERENCE), $e->getCode(), $e);
            }
        }

        $allowedAttributes = $this->getAllowedAttributes($data::class, $context, true);
        foreach ($metadata->getProperties($scenario) as $property) {
            if ($allowedAttributes && !\in_array($property->getName(), $allowedAttributes)) {
                continue;
            }

            $context = $this->getAttributeNormalizationContext($data, $property->getName(), $context);

            if (($accessor = $property->getAccessor()) && ($class = $property->getClass())) {
                try {
                    $this->modelMetadataFactory->getMetadataFor($class);
                    $context[self::NESTING_PATH] ??= [];
                    $context[self::NESTING_PATH][] = $accessor;
                } catch (MissingMetadataException) {
                }
            }

            try {
                $attributeValue = $property->getName() === $this->classDiscriminatorResolver?->getMappingForMappedObject($data)?->getTypeProperty()
                    ? $this->classDiscriminatorResolver?->getTypeForMappedObject($data)
                    : $this->propertyAccessor->getValue($data, $property->getName());
            } catch (UninitializedPropertyException|\Error $e) {
                if (($context[self::SKIP_UNINITIALIZED_VALUES] ?? $this->defaultContext[self::SKIP_UNINITIALIZED_VALUES] ?? true) && $this->isUninitializedValueError($e)) {
                    continue;
                }
                throw $e;
            }

            if (null !== $attributeValue) {
                $type = $property->getType();
                if ($this->typeContainsBool($type)) {
                    if ($attributeValue === $property->getTrueValue() || \in_array($attributeValue, self::TRUE_VALUES, true)) {
                        $attributeValue = $property->getTrueValue() ?? true;
                    } elseif ($attributeValue === $property->getFalseValue() || \in_array($attributeValue, self::FALSE_VALUES, true)) {
                        $attributeValue = $property->getFalseValue() ?? false;
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

            if (\is_array($normalizedValue) && 0 === \count($normalizedValue)) {
                continue;
            }

            if ($property->isRoot()) {
                $target = &$context[self::ROOT_DATA];
            } else {
                $target = &$normalizedData;
            }

            if ($property->hasAccessor()) {
                $accessor = $property->getAccessor();
                if (($relative = $property->getRelative()) && isset($context[self::NESTING_PATH])) {
                    $parts = \array_slice($context[self::NESTING_PATH], 0, -$relative);
                    $parts[] = $accessor;
                    $accessor = implode('', $parts);
                    $target = &$context[self::ROOT_DATA];
                }

                try {
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

        foreach ($metadata->getNormalizers($scenario) as $normalizer) {
            $attribute = $normalizer->getPropertyName();
            if ($allowedAttributes && !\in_array($attribute, $allowedAttributes)) {
                continue;
            }

            if ($data::class !== $normalizer->getClass()) {
                throw new MappingException(\sprintf('Model class "%s" does not match the expected normalizer class "%s".', $data::class, $normalizer->getClass()));
            }

            try {
                $arguments = [];
                foreach ($normalizer->getArguments() as $argument) {
                    $arguments[$argument->getName()] = $argument->getValue();
                }
                $normalized = $data->{$normalizer->getMethod()}(...$arguments);
            } catch (\Throwable $e) {
                throw new BadMethodCallException(\sprintf('Bad normalizer method call: %s', $e->getMessage()), 0, $e);
            }

            if ('__toString' === $normalizer->getMethod()) {
                return $normalized;
            } elseif (null !== $accessor = $normalizer->getAccessor()) {
                $this->propertyAccessor->setValue($normalizedData, $accessor, $normalized);
            } else {
                if (!\is_array($normalized)) {
                    throw new MappingException(\sprintf('Normalizer %s::%s() without accessor must return an array.', $metadata->getClass(), $normalizer->getMethod()));
                }
                $normalizedData = array_merge($normalizedData, $normalized);
            }
        }

        return $normalizedData;
    }

    /**
     * Checks whether the given attribute is allowed for the given class or object.
     * This implementation adds Symfony 8.0 improvements with discriminator property handling.
     *
     * @protected (called from parent class)
     */
    protected function isAllowedAttribute($classOrObject, string $attribute, ?string $format = null, array $context = []): bool
    {
        // Check if this is the discriminator type property - if so, it's always allowed
        if ($this->classDiscriminatorResolver?->getMappingForMappedObject($classOrObject)?->getTypeProperty() === $attribute) {
            return true;
        }

        // Delegate to parent implementation for standard checks
        return parent::isAllowedAttribute($classOrObject, $attribute, $format, $context);
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

    /**
     * Determines if the given type contains a boolean by checking union and wrapped types.
     */
    private function typeContainsBool(Type $type): bool
    {
        $t = $type;
        if ($t instanceof UnionType) {
            foreach ($t->getTypes() as $u) {
                if ($this->typeContainsBool($u)) {
                    return true;
                }
            }

            return false;
        }
        while ($t instanceof WrappingTypeInterface) {
            $t = $t->getWrappedType();
        }

        return method_exists($t, 'getTypeIdentifier') && TypeIdentifier::BOOL === $t->getTypeIdentifier();
    }
}
