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

use Symfony\Component\Serializer\Attribute\Context;

trait ContextAwareMetadataTrait
{
    private ?Context $context = null;

    public function setContext(Context $context): void
    {
        $this->context = $context;
    }

    public function getContext(): array
    {
        return $this->context?->getContext() ?? [];
    }

    public function getNormalizationContext(): array
    {
        return array_merge($this->context?->getNormalizationContext() ?? [], $this->getContext());
    }

    public function getDenormalizationContext(): array
    {
        return array_merge($this->context?->getDenormalizationContext() ?? [], $this->getContext());
    }
}
