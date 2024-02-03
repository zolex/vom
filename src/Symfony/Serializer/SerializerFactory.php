<?php

/*
 * This file is part of the VOM package.
 *
 * (c) Andreas Linden <zlx@gmx.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zolex\VOM\Symfony\Serializer;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer as SymfonyObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\UnwrappingDenormalizer;
use Symfony\Component\Serializer\Serializer;
use Zolex\VOM\Serializer\Normalizer\BooleanNormalizer;
use Zolex\VOM\Serializer\Normalizer\CommonFlagNormalizer;
use Zolex\VOM\Serializer\Normalizer\ObjectNormalizer;

class SerializerFactory
{
    public static function create(ObjectNormalizer $objectNormalizer, BooleanNormalizer $booleanNormalizer, CommonFlagNormalizer $commonFlagNormalizer): Serializer
    {
        return new Serializer(
            [
                new UnwrappingDenormalizer(),
                $objectNormalizer,
                $booleanNormalizer,
                $commonFlagNormalizer,
                new DateTimeNormalizer(),
                new JsonSerializableNormalizer(),
                new ArrayDenormalizer(),
                new SymfonyObjectNormalizer(),
            ],
            [
                new JsonEncoder(),
            ]
        );
    }
}
