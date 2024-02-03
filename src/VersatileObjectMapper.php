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

namespace Zolex\VOM;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\PaginatorInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Zolex\VOM\Metadata\Factory\Exception\RuntimeException;
use Zolex\VOM\Metadata\Factory\ModelMetadataFactoryInterface;
use Zolex\VOM\Metadata\ModelMetadata;
use Zolex\VOM\Metadata\PropertyMetadata;

final class VersatileObjectMapper implements NormalizerInterface, NormalizerAwareInterface, DenormalizerInterface, DenormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use NormalizerAwareTrait;

    public const ROOT_FALLBACK = 'root_fallback';
    private const ROOT_DATA = '__root_data';
    private const PATH = '__normalization_path';

    public function __construct(
        private readonly ModelMetadataFactoryInterface $modelMetadataFactory,
        private readonly PropertyAccessorInterface $propertyAccessor,
    ) {
    }

    private array $filters;

    public function getSupportedTypes(?string $format): array
    {
        // TODO: do we need other iterable/traversable?
        return [
            'object' => true,
            'native-array' => true,
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        // do not normalize api-platform paginator
        if ($data instanceof PaginatorInterface) {
            return false;
        }

        // do not normalize when in api platform operation and not explicitly requested to normalize.
        // otherwise we would map it back to its source structure instead of returning a nice ApiResource!
        if ($this->skipApiPlatformOperation($context)) {
            return false;
        }

        return \is_object($data) || \is_array($data);
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $format ??= 'array';
        if ('object' === $format) {
            throw new InvalidArgumentException('To get an object use the "array" format and then call the toObject($array) on the VersatileObjectMapper.');
        }

        if (!\is_object($object)) {
            throw new \InvalidArgumentException('First argument must be an object.');
        }

        $context[self::PATH] ??= [];
        $data = $context[AbstractNormalizer::OBJECT_TO_POPULATE] ?? [];
        if (!$metadata = $this->modelMetadataFactory->create($object::class)) {
            return (array) $object;
        }

        foreach ($metadata->getProperties() as $property) {
            $propertyName = $property->getName();
            try {
                $value = $this->propertyAccessor->getValue($object, $propertyName);
            } catch (\Throwable) {
                continue;
            }

            $accessor = explode('.', $property->getAccessor());

            if (null === $value && (true === ($context[AbstractObjectNormalizer::SKIP_NULL_VALUES] ?? false))) {
                continue;
            }

            if ($property->isCollection()) {
                foreach ($value as $index => $item) {
                    $childChain = $property->isNested() ? array_merge($property->isRoot() ? [$index] : $context[self::PATH], $accessor, [$index]) : [$index];
                    $data = $this->normalize($item, $format, array_merge($context, [AbstractNormalizer::OBJECT_TO_POPULATE => $data, self::PATH => $childChain]));
                }
            } elseif ($property->isModel() && !$property->isBuiltinClass()) {
                $childChain = $property->isNested() ? array_merge($property->isRoot() ? [] : $context[self::PATH], $accessor) : [];
                $data = $this->normalize($value, $format, array_merge($context, [AbstractNormalizer::OBJECT_TO_POPULATE => $data, self::PATH => $childChain]));
            } else {
                if ($value instanceof \DateTimeInterface) {
                    $value = $value->format($property->getDateTimeFormat());
                }
                $childAccessor = sprintf('[%s]', implode('][', array_merge($context[self::PATH], $accessor)));
                $this->propertyAccessor->setValue($data, $childAccessor, $value);
            }

            unset($value);
            unset($accessor);
            unset($childAccessor);
            unset($childChain);
        }

        return $data;
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

    protected function skipApiPlatformOperation(array $context): bool
    {
        return ((isset($context['operation']) && \is_object($context['operation']) && $context['operation'] instanceof Operation)
            || (isset($context['root_operation']) && \is_object($context['root_operation']) && $context['root_operation'] instanceof Operation))
            && (!isset($context['vom']) || !$context['vom']);
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        // do not denormalize when in an api platform operation and not explicitly requested to denormalize.
        // otherwise we would break api-platform's standard behavior by just installing VOM.
        if ($this->skipApiPlatformOperation($context)) {
            return false;
        }

        if (str_ends_with($type, '[]')) {
            $type = substr($type, 0, -2);
        }

        return null !== $this->modelMetadataFactory->create($type);
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (null === $data) {
            return null;
        }

        if (!isset($this->denormalizer)) {
            $this->denormalizer = &$this;
        }

        if (\is_array($data)) {
            if (array_is_list($data) && str_ends_with($type, '[]')) {
                $array = [];
                foreach ($data as $item) {
                    $array[] = $this->denormalizer->denormalize($item, substr($type, 0, -2), $format, $context);
                }

                return $array;
            }

            $data = $this->toObject($data);
        }

        $context[self::ROOT_FALLBACK] ??= false;
        $context[self::ROOT_DATA] ??= $data;
        $context[AbstractNormalizer::GROUPS] ??= [];
        if (!\is_array($context[AbstractNormalizer::GROUPS])) {
            $context[AbstractNormalizer::GROUPS] = [$context[AbstractNormalizer::GROUPS]];
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

    private function denormalizeProperty(mixed $data, PropertyMetadata $property, ?string $format = null, array $context = []): mixed
    {
        if (\count($context[AbstractNormalizer::GROUPS])
            && !\count(array_intersect($property->getGroups(), $context[AbstractNormalizer::GROUPS]))) {
            return null;
        }

        $accessor = $property->getAccessor();
        try {
            $value = $this->propertyAccessor->getValue($data, $accessor);
        } catch (\Throwable) {
            if (!$property->isNested() || true === $context[self::ROOT_FALLBACK]) {
                $value = $context[self::ROOT_DATA];
            } elseif ($property->isNested() && $property->isRoot()) {
                try {
                    $value = $this->propertyAccessor->getValue($context[self::ROOT_DATA], $accessor);
                } catch (\Throwable) {
                    $value = null;
                }
            } else {
                $value = null;
            }
        }

        if (null === $value && (true === ($context[AbstractObjectNormalizer::SKIP_NULL_VALUES] ?? false))) {
            return null;
        }

        if ($property->isCollection() && $collectionType = $property->getCollectionType()) {
            $value = $this->denormalizer->denormalize($value, $collectionType.'[]', $format, $context);
        } elseif (\is_string($value) && $property->isDateTime()) {
            $class = $property->getType();
            $value = new $class($value);
        } elseif ($property->isBool()) {
            if ($property->isFlag()) {
                if (\is_array($data)) {
                    // common flag
                    if (\in_array($accessor, $data, true)) {
                        $value = true;
                    } elseif (\in_array('!'.$accessor, $data, true)) {
                        $value = false;
                    } else {
                        $value = null;
                    }
                } elseif (\is_object($data)) {
                    // model flag nested value
                    $value = !$property->isFalse($value);
                }
            } elseif (null !== $value) {
                $value = $property->isTrue($value);
            }
        } elseif (null !== $value && $property->isModel()) {
            // we know it's a VOM model, so call our own method directly to avoid other normalizers "stealing" it
            $value = $this->denormalize($value, $property->getType(), $format, $context);
        } elseif ($this !== $this->denormalizer) {
            // only call if we are in the symfony normalizer chain, otherwise it results in an endless recursion
            $value = $this->denormalizer->denormalize($value, $property->getType() ?? $property->getBuiltinType(), $format, $context);
        }

        return $value;
    }

    /**
     * @codeCoverageIgnore
     * Unused, just here as a backup :P
     */
    private function getNormalizedFields(string|ModelMetadata $metadata, array $context = []): array
    {
        $fields = [];
        $context[self::PATH] ??= [];
        $context[AbstractNormalizer::GROUPS] ??= [];
        if (\is_string($metadata)) {
            $metadata = $this->modelMetadataFactory->create($metadata);
        }

        foreach ($metadata->getProperties() as $property) {
            if ($property->isNested()) {
                $accessChain = array_merge($context[self::PATH], [$property->getAccessor()]);
            } else {
                $accessChain = [$property->getAccessor()];
            }

            if (\count(array_intersect($property->getGroups(), $context[AbstractNormalizer::GROUPS]))) {
                $fields[] = implode('.', $accessChain);
            }

            if ($nestedModelMetadata = $property->getModelMetadata()) {
                $fields = array_merge($fields, $this->getNormalizedFields($nestedModelMetadata, $context));
            }
        }

        return $fields;
    }
}
