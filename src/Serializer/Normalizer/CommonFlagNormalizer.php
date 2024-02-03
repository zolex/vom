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

class CommonFlagNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public const TYPE = 'vom-flag';
    public const CONTEXT_NAME = '__vom_common_flag_name';

    public function getSupportedTypes(?string $format): array
    {
        return [
            '*' => true,
        ];
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return self::TYPE === $type && \is_array($data) && isset($context[self::CONTEXT_NAME]);
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (!isset($context[self::CONTEXT_NAME])) {
            return null;
        }

        if (\in_array($context[self::CONTEXT_NAME], $data, true)) {
            return true;
        }

        if (\in_array('!'.$context[self::CONTEXT_NAME], $data, true)) {
            return false;
        }

        return null;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return \is_bool($data)
            && isset($context[ObjectNormalizer::CONTEXT_PROPERTY])
            && $context[ObjectNormalizer::CONTEXT_PROPERTY] instanceof PropertyMetadata
            && $context[ObjectNormalizer::CONTEXT_PROPERTY]->isFlag();
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        if (!\is_bool($object)
            || !isset($context[ObjectNormalizer::CONTEXT_PROPERTY])
            || !$context[ObjectNormalizer::CONTEXT_PROPERTY] instanceof PropertyMetadata
            || !$context[ObjectNormalizer::CONTEXT_PROPERTY]->isFlag()) {
            return null;
        }

        if (true === $object) {
            return $context[ObjectNormalizer::CONTEXT_PROPERTY]->getName();
        }

        if (false === $object) {
            return '!'.$context[ObjectNormalizer::CONTEXT_PROPERTY]->getName();
        }

        return null;
    }
}
