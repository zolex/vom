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

namespace Zolex\VOM\Test\Fixtures;

use Symfony\Component\Serializer\SerializerInterface;

class DummySerializer implements SerializerInterface
{
    public function serialize(mixed $data, string $format, array $context = []): string
    {
        return '';
    }

    public function deserialize(mixed $data, string $type, string $format, array $context = []): mixed
    {
        return null;
    }
}
