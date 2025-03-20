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

#[VOM\Model(extractor: '/^(?<filename>.+),tag:(.*),visibility:(?<visibility>visible|hidden)/')]
class RegexpExtractorModel
{
    #[VOM\Property]
    public string $filename;
    #[VOM\Property(accessor: '[2]')]
    public string $tag;
    #[VOM\Property('[visibility]', map: ['visible' => true, 'hidden' => false])]
    public bool $isVisible;

    #[VOM\Normalizer]
    public function __toString(): string
    {
        return $this->filename.',tag:'.$this->tag.',visibility:'.($this->isVisible ? 'visible' : 'hidden');
    }
}
