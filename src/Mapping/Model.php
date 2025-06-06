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

namespace Zolex\VOM\Mapping;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Model
{
    public function __construct(private readonly ?array $factory = null, private readonly ?string $extractor = null)
    {
    }

    public function getFactory(): ?array
    {
        return $this->factory;
    }

    public function getExtractor(): ?string
    {
        return $this->extractor;
    }
}
