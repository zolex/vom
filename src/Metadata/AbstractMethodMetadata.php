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

abstract class AbstractMethodMetadata
{
    public function __construct(
        private readonly string $method,
    ) {
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getAttribute(): string
    {
        $accessorOrMutator = preg_match('/^(get|is|has|set)(.+)$/i', $this->method, $matches);
        if ($accessorOrMutator) {
            return lcfirst($matches[2]);
        }

        return $this->method;
    }
}
