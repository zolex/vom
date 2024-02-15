<?php

/*
 * This file is part of the VOM package.
 *
 * (c) Andreas Linden <zlx@gmx.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zolex\VOM\Metadata;

/**
 * Base class for all VOM Attributes that can be added on a method.
 */
abstract class AbstractMethodMetadata
{
    public function __construct(
        private readonly string $method,
        private readonly ?string $virtualPropertyName,
    ) {
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPropertyName(): ?string
    {
        return $this->virtualPropertyName;
    }
}
