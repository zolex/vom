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
use Zolex\VOM\Metadata\PropertyMetadata;

final class BooleanNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public const TYPE = 'vom-bool';
    public const TRUE_VALUES = [true, 1, '1', 'TRUE', 'true', 'T', 't', 'ON', 'on', 'YES', 'yes', 'Y', 'y'];
    public const FALSE_VALUES = [false, 0, '0', 'FALSE', 'false', 'F', 'f', 'OFF', 'off', 'NO', 'no', 'N', 'n'];

    public function getSupportedTypes(?string $format): array
    {
        return [
            '*' => true,
        ];
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return 'vom-bool' === $type;
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (isset($context[ObjectNormalizer::CONTEXT_PROPERTY])
            && $context[ObjectNormalizer::CONTEXT_PROPERTY] instanceof PropertyMetadata
            && $context[ObjectNormalizer::CONTEXT_PROPERTY]->isBool()) {
            if (null !== $trueValue = $context[ObjectNormalizer::CONTEXT_PROPERTY]->getTrueValue()) {
                return $data === $trueValue;
            }

            if (null !== $falseValue = $context[ObjectNormalizer::CONTEXT_PROPERTY]->getFalseValue()) {
                return $data === $falseValue;
            }

            if (\in_array($data, self::TRUE_VALUES, true)) {
                return true;
            }

            if (\in_array($data, self::FALSE_VALUES, true)) {
                return false;
            }
        }

        return null;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return \is_bool($data)
            && isset($context[ObjectNormalizer::CONTEXT_PROPERTY])
            && $context[ObjectNormalizer::CONTEXT_PROPERTY] instanceof PropertyMetadata
            && $context[ObjectNormalizer::CONTEXT_PROPERTY]->isBool()
            && !$context[ObjectNormalizer::CONTEXT_PROPERTY]->isFlag();
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        if (!\is_bool($object)
            || !isset($context[ObjectNormalizer::CONTEXT_PROPERTY])
            || !$context[ObjectNormalizer::CONTEXT_PROPERTY] instanceof PropertyMetadata
            || !$context[ObjectNormalizer::CONTEXT_PROPERTY]->isBool()
            || $context[ObjectNormalizer::CONTEXT_PROPERTY]->isFlag()) {
            return null;
        }

        if (true === $object) {
            return $context[ObjectNormalizer::CONTEXT_PROPERTY]->getTrueValue() ?? true;
        }

        if (false === $object) {
            return $context[ObjectNormalizer::CONTEXT_PROPERTY]->getFalseValue() ?? false;
        }

        return null;
    }
}
