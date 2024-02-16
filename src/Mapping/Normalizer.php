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

#[\Attribute(\Attribute::TARGET_METHOD)]
final class Normalizer
{
    public function __construct(private readonly ?string $accessor = null)
    {
    }

    public function getAccessor(): ?string
    {
        return $this->accessor;
    }
}
