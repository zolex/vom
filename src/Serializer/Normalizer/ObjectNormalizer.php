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
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;
use Zolex\VOM\Metadata\Factory\Exception\RuntimeException;
use Zolex\VOM\Metadata\Factory\ModelMetadataFactory;
use Zolex\VOM\Metadata\PropertyMetadata;

class ObjectNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    use SerializerAwareTrait;

    public const ROOT_FALLBACK = 'root_fallback';
    private const ROOT_DATA = '__root_data';
    private const PATH = '__normalization_path';

    public function __construct(
        private ModelMetadataFactory $modelMetadataFactory,
        private PropertyAccessorInterface $propertyAccessor,
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
        return (\is_array($data) || \is_object($data)) && $this->modelMetadataFactory->create($type);
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (null === $data) {
            return null;
        }

        if (\is_array($data) && !array_is_list($data)) {
            $data = $this->toObject($data);
        }

        $metadata = $this->modelMetadataFactory->create($type);

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
                // TODO: check if it makes sense to allow setting them them again (override)
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

    public function toObject(array|object $data): object|array
    {
        if (\is_array($data) && array_is_list($data)) {
            $array = [];
            foreach ($data as $key => $value) {
                if (\is_array($value)) {
                    $array[$key] = $this->toObject($value);
                } else {
                    $array[$key] = $value;
                }
            }

            return $array;
        } else {
            $object = new \stdClass();
            foreach ($data as $key => $value) {
                if (\is_array($value)) {
                    $object->{$key} = $this->toObject($value);
                } else {
                    $object->{$key} = $value;
                }
            }

            return $object;
        }
    }

    private function denormalizeProperty(mixed $data, PropertyMetadata $property, ?string $format = null, array $context = []): mixed
    {
        if ($property->isFlag()) {
            $value = $data;
            $context[CommonFlagNormalizer::CONTEXT_NAME] = $property->getName();
        } else {
            $accessor = $property->getAccessor();
            try {
                if ($property->isNested()) {
                    $value = $this->propertyAccessor->getValue($data, $accessor);
                } else {
                    $value = $this->propertyAccessor->getValue($context[self::ROOT_DATA], $accessor);
                }
            } catch (\Throwable $e) {
                $value = null;
            }

            if (null === $value) {
                return null;
            }
        }

        return $this->serializer->denormalize($value, $property->getType(), $format, $context);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return false;
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        return ['object' => 'xD'];
    }
}
