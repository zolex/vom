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

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
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

    public const CONTEXT_PROPERTY = '__vom_property';
    public const CONTEXT_ROOT_DATA = '__root_data';

    public function __construct(
        ClassMetadataFactoryInterface $classMetadataFactory,
        private readonly ModelMetadataFactoryInterface $modelMetadataFactory,
        private readonly PropertyAccessorInterface $propertyAccessor,
        array $defaultContext = [],
    ) {
        parent::__construct($classMetadataFactory, null, $defaultContext);
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

        $metadata = $this->modelMetadataFactory->getMetadataFor($type);
        $context[self::CONTEXT_ROOT_DATA] ??= $data;

        $constructorArguments = [];
        foreach ($metadata->getConstructorArguments() as $argument) {
            $value = $this->denormalizeProperty($type, $data, $argument, $format, $context);
            if (null === $value && $argument->hasDefaultValue()) {
                $value = $argument->getDefaultValue();
            }

            $constructorArguments[$argument->getName()] = $value;
        }

        $model = new $type(...$constructorArguments);

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
            if ($metadata->hasConstructorArgument($property->getName())) {
                // skip, because they have already been injected in the constructor
                continue;
            }

            if ($allowedAttributes && !\in_array($property->getName(), $allowedAttributes)) {
                continue;
            }

            $value = $this->denormalizeProperty($type, $data, $property, $format, $context);
            try {
                $this->propertyAccessor->setValue($model, $property->getName(), $value);
            } catch (\Throwable) {
            }
        }

        return $model;
    }

    private function denormalizeProperty(string $type, mixed $data, PropertyMetadata $property, ?string $format = null, array $context = []): mixed
    {
        $context = $this->getAttributeDenormalizationContext($type, $property->getName(), $context);
        $context[self::CONTEXT_PROPERTY] = &$property;
        if ($property->isRoot()) {
            $data = &$context[self::CONTEXT_ROOT_DATA];
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
            $result = $this->serializer->denormalize($value, $property->getType(), $format, $context);
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
        if (!isset($context[self::CONTEXT_ROOT_DATA])) {
            $context[self::CONTEXT_ROOT_DATA] = &$data;
        }

        $allowedAttributes = $this->getAllowedAttributes($object::class, $context, true);
        foreach ($metadata->getProperties() as $property) {
            try {
                if ($allowedAttributes && !\in_array($property->getName(), $allowedAttributes)) {
                    continue;
                }

                $context = $this->getAttributeNormalizationContext($object, $property->getName(), $context);
                $context[self::CONTEXT_PROPERTY] = &$property;

                $accessedValue = $this->propertyAccessor->getValue($object, $property->getName());
                $normalizedValue = $this->serializer->normalize($accessedValue, $format, $context);

                if ($property->isRoot()) {
                    $target = &$context[self::CONTEXT_ROOT_DATA];
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
                    } catch (\Throwable $e) {
                        $x = 1;
                    }
                } else {
                    $target = array_merge($target, $normalizedValue);
                }
            } catch (\Throwable) {
            }
        }

        foreach ($metadata->getNormalizers() as $normalizer) {
            $context = $this->getAttributeNormalizationContext($object, $normalizer->getAttribute(), $context);
            if ($allowedAttributes && !\in_array($normalizer->getAttribute(), $allowedAttributes)) {
                continue;
            }

            // TODO: allow an accessor on the normalizer attribute
            $data = array_merge($data, $object->{$normalizer->getMethod()}());
        }

        return $data;
    }
}
