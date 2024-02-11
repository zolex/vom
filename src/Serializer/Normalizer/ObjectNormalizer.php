<?php

/*
 * This file is part of the VOM package.
 *
 * (c) Andreas Linden <zlx@gmx.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zolex\VOM\Serializer\Normalizer;

use Symfony\Component\PropertyAccess\Exception\UninitializedPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorResolverInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;
use Zolex\VOM\Metadata\Factory\Exception\MappingException;
use Zolex\VOM\Metadata\Factory\ModelMetadataFactoryInterface;
use Zolex\VOM\Metadata\PropertyMetadata;

final class ObjectNormalizer extends AbstractNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    use SerializerAwareTrait;

    public const VOM_PROPERTY = 'vom_property_metadata';
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

    public function getSupportedTypes(?string $format): array
    {
        return [
            'object' => true,
            'native-array' => true,
        ];
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        if (!isset($context['vom']) || !$context['vom']) {
            return false;
        }

        return (\is_array($data) || \is_object($data)) && $this->modelMetadataFactory->getMetadataFor($type);
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (null === $data) {
            return null;
        }

        $context[self::ROOT_DATA] ??= $data;

        $model = $this->createInstance($data, $type, $context, $format);
        $metadata = $this->modelMetadataFactory->getMetadataFor($model::class);

        $allowedAttributes = $this->getAllowedAttributes($type, $context, true);
        foreach ($metadata->getDenormalizers() as $denormalizer) {
            $attribute = $denormalizer->getAttribute();
            if ($allowedAttributes && !\in_array($attribute, $allowedAttributes)) {
                continue;
            }

            $context = $this->getAttributeDenormalizationContext($type, $attribute, $context);
            $methodArguments = [];
            foreach ($denormalizer->getArguments() as $property) {
                $methodArguments[$property->getName()] = $this->denormalizeProperty($type, $data, $property, $format, $context);
            }

            try {
                $model->{$denormalizer->getMethod()}(...$methodArguments);
            } catch (\Throwable $e) {
                throw new MappingException(sprintf('Unable to call method %s on %s', $denormalizer->getMethod(), $model::class), 0, $e);
            }
        }

        foreach ($metadata->getProperties() as $property) {
            if ($allowedAttributes && !\in_array($property->getName(), $allowedAttributes)) {
                continue;
            }

            $context = $this->getAttributeDenormalizationContext($type, $property->getName(), $context);
            $value = $this->denormalizeProperty($type, $data, $property, $format, $context);
            try {
                $this->propertyAccessor->setValue($model, $property->getName(), $value);
            } catch (\Throwable) {
            }
        }

        return $model;
    }

    protected function createInstance(array &$data, string $class, array &$context, ?string $format): object
    {
        if ($class !== $mappedClass = $this->getMappedClass($data, $class, $context)) {
            return $this->createInstance($data, $mappedClass, $context, $format);
        }

        $constructorArguments = [];
        $metadata = $this->modelMetadataFactory->getMetadataFor($class);
        foreach ($metadata->getConstructorArguments() as $argument) {
            $value = $this->denormalizeProperty($class, $data, $argument, $format, $context);
            if (null === $value && $argument->hasDefaultValue()) {
                $value = $argument->getDefaultValue();
            }

            $constructorArguments[$argument->getName()] = $value;
        }

        return new $class(...$constructorArguments);
    }

    private function getMappedClass(array $data, string $class, array $context): string
    {
        if (null !== $object = $this->extractObjectToPopulate($class, $context, self::OBJECT_TO_POPULATE)) {
            return $object::class;
        }

        if (!$mapping = $this->classDiscriminatorResolver?->getMappingForClass($class)) {
            return $class;
        }

        if (null === $type = $data[$mapping->getTypeProperty()] ?? null) {
            throw NotNormalizableValueException::createForUnexpectedDataType(sprintf('Type property "%s" not found for the abstract object "%s".', $mapping->getTypeProperty(), $class), null, ['string'], isset($context['deserialization_path']) ? $context['deserialization_path'].'.'.$mapping->getTypeProperty() : $mapping->getTypeProperty(), false);
        }

        if (null === $mappedClass = $mapping->getClassForType($type)) {
            throw NotNormalizableValueException::createForUnexpectedDataType(sprintf('The type "%s" is not a valid value.', $type), $type, ['string'], isset($context['deserialization_path']) ? $context['deserialization_path'].'.'.$mapping->getTypeProperty() : $mapping->getTypeProperty(), true);
        }

        return $mappedClass;
    }

    private function denormalizeProperty(string $type, mixed $data, PropertyMetadata $property, ?string $format = null, array $context = []): mixed
    {
        $context[self::VOM_PROPERTY] = &$property;
        if ($property->isRoot()) {
            $data = &$context[self::ROOT_DATA];
        }

        $accessor = $property->getAccessor();
        try {
            if ($property->isNested() && !$property->isFlag()) {
                $value = $this->propertyAccessor->getValue($data, $accessor);
            } else {
                $value = &$data;
            }
        } catch (\Throwable) {
            $value = null;
        }

        if (null === $value) {
            return null;
        }

        try {
            $result = $this->serializer->denormalize($value, $property->getType(), $format, $this->createChildContext($context, $property->getName(), $format));
        } catch (NotNormalizableValueException $e) {
            if ($e->canUseMessageForUser() && $e->getExpectedTypes()) {
                // re-throw with additional information
                $message = sprintf(
                    'The type of the property "%s" must be "%s", "%s" given.',
                    $property->getAccessor(),
                    implode(', ', $e->getExpectedTypes()),
                    $e->getCurrentType()
                );
                throw NotNormalizableValueException::createForUnexpectedDataType($message, $value, $e->getExpectedTypes(), $e->getPath(), true, $e->getCode(), $e);
            } else {
                throw $e;
            }
        }

        if ($arrayAccessClass = $property->getArrayAccessType()) {
            return new $arrayAccessClass($result);
        }

        return $result;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        if (!isset($context['vom']) || !$context['vom']) {
            return false;
        }

        return \is_object($data) && null !== $this->modelMetadataFactory->getMetadataFor($data::class);
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        if (!$metadata = $this->modelMetadataFactory->getMetadataFor($object::class)) {
            return null;
        }

        $data = [];
        if (!isset($context[self::ROOT_DATA])) {
            $context[self::ROOT_DATA] = &$data;
        }

        if ($this->isCircularReference($object, $context)) {
            return $this->handleCircularReference($object, $format, $context);
        }

        $allowedAttributes = $this->getAllowedAttributes($object::class, $context, true);
        foreach ($metadata->getProperties() as $property) {
            try {
                if ($allowedAttributes && !\in_array($property->getName(), $allowedAttributes)) {
                    continue;
                }

                $context = $this->getAttributeNormalizationContext($object, $property->getName(), $context);
                $context[self::VOM_PROPERTY] = &$property;

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

                $normalizedValue = $this->serializer->normalize($attributeValue, $format, $context);

                if (null === $normalizedValue && ($context[self::SKIP_NULL_VALUES] ?? $this->defaultContext[self::SKIP_NULL_VALUES] ?? false)) {
                    continue;
                }

                if ($property->isRoot()) {
                    $target = &$context[self::ROOT_DATA];
                } else {
                    $target = &$data;
                }

                if ($property->isFlag()) {
                    if (null !== $normalizedValue) {
                        $target[] = $normalizedValue;
                    }
                } elseif ($property->isNested()) {
                    try {
                        $accessor = $property->getAccessor();
                        $this->propertyAccessor->setValue($target, $accessor, $normalizedValue);
                    } catch (\Throwable) {
                    }
                } else {
                    $target = array_merge($target, $normalizedValue);
                }
            } catch (\Throwable) {
            }
        }

        foreach ($metadata->getNormalizers() as $normalizer) {
            $attribute = $normalizer->getAttribute();
            if ($allowedAttributes && !\in_array($attribute, $allowedAttributes)) {
                continue;
            }

            $data = array_merge($data, $object->{$normalizer->getMethod()}());
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
