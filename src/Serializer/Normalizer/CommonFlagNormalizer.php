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
use Zolex\VOM\Metadata\Factory\Exception\RuntimeException;
use Zolex\VOM\Metadata\Factory\ModelMetadataFactory;

class CommonFlagNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function __construct(private ModelMetadataFactory $modelMetadataFactory)
    {
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            'native-array' => true,
        ];
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return \is_array($data) && $this->modelMetadataFactory->create($type);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return false;
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (!$metadata = $this->modelMetadataFactory->create($type)) {
            throw new RuntimeException('No metadata available for '.$type);
        }

        return ['commonflag' => 'xD'];
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        // TODO: Implement normalize() method.
    }
}
