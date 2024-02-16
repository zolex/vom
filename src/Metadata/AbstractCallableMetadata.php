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

abstract class AbstractCallableMetadata
{
    public function __construct(
        private readonly string $class,
        private readonly string $method,
        /* @var array|ArgumentMetadata[] $arguments */
        private readonly array $arguments = [],
    ) {
    }

    public function getCallable(): array
    {
        return [$this->class, $this->method];
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getLongMethodName(): string
    {
        return sprintf('%s::%s()', $this->class, $this->method);
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }
}
