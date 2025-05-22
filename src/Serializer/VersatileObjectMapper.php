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

namespace Zolex\VOM\Serializer;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Zolex\VOM\Exception\InvalidArgumentException;

final class VersatileObjectMapper implements NormalizerInterface, DenormalizerInterface, SerializerInterface
{
    public function __construct(private readonly SerializerInterface|NormalizerInterface|DenormalizerInterface $decorated)
    {
        if (!$this->decorated instanceof NormalizerInterface) {
            throw new InvalidArgumentException('The decorated serializer must implement the NormalizerInterface');
        }

        if (!$this->decorated instanceof DenormalizerInterface) {
            throw new InvalidArgumentException('The decorated serializer must implement the DenormalizerInterface');
        }
    }

    /**
     * Makes a deep conversion to an object. Every array will become an object, except for indexed arrays.
     */
    public static function toObject(array|object $data): object|array
    {
        if (\is_array($data) && array_is_list($data)) {
            $array = [];
            foreach ($data as $key => $value) {
                if (\is_array($value)) {
                    $array[$key] = self::toObject($value);
                } else {
                    $array[$key] = $value;
                }
            }

            return $array;
        } else {
            $object = new \stdClass();
            foreach ($data as $key => $value) {
                if (\is_array($value)) {
                    $object->{$key} = self::toObject($value);
                } else {
                    $object->{$key} = $value;
                }
            }

            return $object;
        }
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        $context['vom'] = true;

        return $this->decorated->denormalize($data, $type, $format, $context);
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        $context['vom'] = true;

        return $this->decorated->supportsDenormalization($data, $type, $format, $context);
    }

    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $context['vom'] = true;

        return $this->decorated->normalize($data, $format, $context);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        $context['vom'] = true;

        return $this->decorated->supportsNormalization($data, $format, $context);
    }

    /**
     * @return array<class-string|'*'|'object'|string, bool|null>
     */
    public function getSupportedTypes(?string $format): array
    {
        return $this->decorated->getSupportedTypes($format);
    }

    public function serialize(mixed $data, string $format, array $context = []): string
    {
        $context['vom'] = true;

        return $this->decorated->serialize($data, $format, $context);
    }

    public function deserialize(mixed $data, string $type, string $format, array $context = []): mixed
    {
        $context['vom'] = true;

        return $this->decorated->deserialize($data, $type, $format, $context);
    }
}
