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

use ApiPlatform\Metadata\Operation;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;
use Zolex\VOM\Metadata\Factory\Exception\RuntimeException;
use Zolex\VOM\Metadata\Factory\ModelMetadataFactoryInterface;
use Zolex\VOM\Metadata\PropertyMetadata;
use Zolex\VOM\Serializer\VersatileObjectMapper;

final class ObjectNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    use SerializerAwareTrait;

    public const CONTEXT_PROPERTY = '__vom_property';
    public const CONTEXT_ROOT_DATA = '__root_data';

    public function __construct(
        private readonly ModelMetadataFactoryInterface $modelMetadataFactory,
        private readonly PropertyAccessorInterface $propertyAccessor,
    ) {
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
        if ($this->skipApiPlatformOperation($context)) {
            return false;
        }

        return (\is_array($data) || \is_object($data)) && $this->modelMetadataFactory->getMetadataFor($type);
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (null === $data) {
            return null;
        }

        if (\is_array($data) && !array_is_list($data)) {
            $data = VersatileObjectMapper::toObject($data);
        }

        $metadata = $this->modelMetadataFactory->getMetadataFor($type);

        $constructorArguments = [];
        foreach ($metadata->getConstructorArguments() as $argument) {
            $value = $this->denormalizeProperty($data, $argument, $format, $context);
            if (null === $value && $argument->hasDefaultValue()) {
                $value = $argument->getDefaultValue();
            }

            $constructorArguments[$argument->getName()] = $value;
        }

        $model = new $type(...$constructorArguments);

        foreach ($metadata->getMethodCalls() as $methodCall) {
            $methodArguments = [];
            foreach ($methodCall->getArguments() as $property) {
                $methodArguments[$property->getName()] = $this->denormalizeProperty($data, $property, $format, $context);
            }

            try {
                $model->{$methodCall->getMethod()}(...$methodArguments);
            } catch (\Throwable $e) {
                throw new RuntimeException(sprintf('Unable to call method %s on %s', $methodCall->getMethod(), $model::class), 0, $e);
            }
        }

        foreach ($metadata->getProperties() as $property) {
            if ($metadata->hasConstructorArgument($property->getName())) {
                // skip, because they have already been injected in the constructor
                continue;
            }

            if (!$this->inContextGroups($property, $context)) {
                continue;
            }

            $value = $this->denormalizeProperty($data, $property, $format, $context);
            try {
                $this->propertyAccessor->setValue($model, $property->getName(), $value);
            } catch (\Throwable) {
            }
        }

        return $model;
    }

    private function denormalizeProperty(mixed $data, PropertyMetadata $property, ?string $format = null, array $context = []): mixed
    {
        $context = array_merge($property->getDenormalizationContext(), $property->getContext(), $context);
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

        return $this->serializer->denormalize($value, $property->getType(), $format, $context);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        if ($this->skipApiPlatformOperation($context)) {
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
        foreach ($metadata->getProperties() as $property) {
            try {
                if (!$this->inContextGroups($property, $context)) {
                    continue;
                }

                $context = array_merge($property->getNormalizationContext(), $context);
                $context[self::CONTEXT_PROPERTY] = &$property;
                $accessedValue = $this->propertyAccessor->getValue($object, $property->getName());
                $normalizedValue = $this->serializer->normalize($accessedValue, $format, $context);

                if ($property->isFlag()) {
                    if (null !== $normalizedValue) {
                        $data[] = $normalizedValue;
                    }
                } elseif ($property->isNested()) {
                    try {
                        $accessor = '['.implode('][', explode('.', $property->getAccessor())).']';
                        $this->propertyAccessor->setValue($data, $accessor, $normalizedValue);
                    } catch (\Throwable) {
                    }
                } else {
                    $data = array_merge($data, $normalizedValue);
                }
            } catch (\Throwable) {
            }
        }

        return $data;
    }

    private function inContextGroups(PropertyMetadata $property, array $context): bool
    {
        if (!isset($context[AbstractNormalizer::GROUPS])) {
            return true;
        }

        foreach ($property->getGroups() as $group) {
            if (\in_array($group, $context[AbstractNormalizer::GROUPS])) {
                return true;
            }
        }

        return false;
    }

    private function skipApiPlatformOperation(array $context): bool
    {
        return ((isset($context['operation']) && $context['operation'] instanceof Operation)
                || (isset($context['root_operation']) && $context['root_operation'] instanceof Operation))
            && (!isset($context['vom']) || !$context['vom']);
    }
}
