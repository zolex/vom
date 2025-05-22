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

namespace Zolex\VOM\Metadata;

/**
 * Wraps key, value and the individual accessor for properties whose accessor is an array.
 */
class AccessorListItemMetadata
{
    public function __construct(
        private string|int $key,
        private string $accessor,
        private mixed $value,
    ) {
    }

    public function getKey(): string|int
    {
        return $this->key;
    }

    public function getAccessor(): string
    {
        return $this->accessor;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->value[$offset] ?? null;
    }
}
