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
 * Base class for all VOM Attributes that can be added on a method and require arguments.
 */
class AbstractMethodWithArgumentsMetadata extends AbstractMethodMetadata
{
    public function __construct(
        string $method,
        /** @var array|ArgumentMetadata[] $arguments */
        private readonly array $arguments,
        ?string $virtualPropertyName = null,
    ) {
        parent::__construct($method, $virtualPropertyName);
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }
}
