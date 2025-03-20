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

namespace Zolex\VOM\Test\Fixtures;

use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class RegexpExtractorProperty
{
    #[VOM\Property(extractor: '/^([^,]+)/')]
    public string $filename;
    #[VOM\Property(extractor: '/tag:([^,]+)/')]
    public string $tag;
    #[VOM\Property(map: ['visible' => true, 'hidden' => false], extractor: '/visibility:(visible|hidden)/')]
    public bool $isVisible;

    #[VOM\Normalizer]
    public function __toString(): string
    {
        return $this->filename.',tag:'.$this->tag.',visibility:'.($this->isVisible ? 'visible' : 'hidden');
    }
}
