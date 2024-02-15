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

use Zolex\VOM\Mapping\Normalizer;

class NormalizerMetadata extends AbstractMethodMetadata
{
    public function __construct(
        string $method,
        ?string $virtualPropertyName,
        private readonly Normalizer $attribute,
    ) {
        parent::__construct($method, $virtualPropertyName);
    }

    public function getAccessor(): ?string
    {
        return $this->attribute->getAccessor();
    }
}
