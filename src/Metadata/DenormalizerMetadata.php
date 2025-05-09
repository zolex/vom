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

class DenormalizerMetadata extends AbstractCallableMetadata
{
    public function __construct(
        string $class,
        string $method,
        /* @var array|ArgumentMetadata[][] $arguments */
        array $arguments = [],
        private readonly ?string $virtualPropertyName = null,
    ) {
        parent::__construct($class, $method, $arguments);
    }

    public function getPropertyName(): ?string
    {
        return $this->virtualPropertyName;
    }
}
