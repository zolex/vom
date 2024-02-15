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

class FactoryMetadata extends AbstractMethodWithArgumentsMetadata
{
    public function __construct(
        string $method,
        /* @var array|PropertyMetadata[] */
        array $arguments,
        private readonly int $priority = 0,
        ?string $virtualPropertyName = null,
    ) {
        parent::__construct($method, $arguments, $virtualPropertyName);
    }

    public function getPriority(): int
    {
        return $this->priority;
    }
}
