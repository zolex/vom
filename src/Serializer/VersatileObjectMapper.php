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
    public function __construct(private readonly SerializerInterface $decorated)
    {
        if (!$this->decorated instanceof NormalizerInterface) {
            throw new InvalidArgumentException('The decorated serializer must implement the NormalizerInterface');
        }

        if (!$this->decorated instanceof DenormalizerInterface) {
            throw new InvalidArgumentException('The decorated serializer must implement the DenormalizerInterface');
        }
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        return $this->decorated->denormalize($data, $type, $format, $context);
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $this->decorated->supportsDenormalization($data, $type, $format, $context);
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        return $this->decorated->normalize($object, $format, $context);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $this->decorated->supportsNormalization($data, $format, $context);
    }

    public function getSupportedTypes(?string $format): array
    {
        return $this->decorated->getSupportedTypes($format);
    }

    public function serialize(mixed $data, string $format, array $context = []): string
    {
        return $this->decorated->serialize($data, $format, $context);
    }

    public function deserialize(mixed $data, string $type, string $format, array $context = []): mixed
    {
        return $this->decorated->deserialize($data, $type, $format, $context);
    }
}
