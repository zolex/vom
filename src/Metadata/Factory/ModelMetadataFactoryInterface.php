<?php

/*
 * This file is part of the VOM package.
 *
 * (c) Andreas Linden <zlx@gmx.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zolex\VOM\Metadata\Factory;

use Zolex\VOM\Metadata\ModelMetadata;

/**
 * Returns a {@see ModelMetadata}.
 */
interface ModelMetadataFactoryInterface
{
    /**
     * If the method was called with the same class name before,
     * the same metadata instance is returned.
     *
     * If the factory was configured with a cache, this method will first look
     * for an existing metadata instance in the cache. If an existing instance
     * is found, it will be returned without further ado.
     *
     * Otherwise, a new metadata instance is created.
     */
    public function getMetadataFor(string $class): ?ModelMetadata;
}
