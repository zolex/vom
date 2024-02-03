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

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class BooleanNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public const TRUE_VALUES = [true, 1, '1', 'TRUE', 'true', 'T', 't', 'ON', 'on', 'YES', 'yes', 'Y', 'y'];
    public const FALSE_VALUES = [false, 0, '0', 'FALSE', 'false', 'F', 'f', 'OFF', 'off', 'NO', 'no', 'N', 'n'];

    public function getSupportedTypes(?string $format): array
    {
        return [
            'string' => true,
            'int' => true,
            'bool' => true,
            'vom-bool' => true,
        ];
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return \in_array($data, self::TRUE_VALUES, true) || \in_array($data, self::FALSE_VALUES, true);
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (\in_array($data, self::TRUE_VALUES, true)) {
            return true;
        }

        if (\in_array($data, self::FALSE_VALUES, true)) {
            return false;
        }

        return null;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return false;
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        return 'yes';
    }
}
