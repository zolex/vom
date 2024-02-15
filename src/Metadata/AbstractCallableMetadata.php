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

abstract class AbstractCallableMetadata implements CallableMetadataInterface
{
    public function __construct(
        private readonly array $callable,
        /* @var array|ArgumentMetadata[] $arguments */
        private readonly array $arguments = [],
    ) {
    }

    public function getCallable(): array
    {
        return $this->callable;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getClass(): string
    {
        return $this->callable[0];
    }

    public function getMethod(): string
    {
        return $this->callable[1];
    }

    public function getLongMethodName(): string
    {
        return sprintf('%s::%s()', $this->callable[0], $this->callable[1]);
    }
}
