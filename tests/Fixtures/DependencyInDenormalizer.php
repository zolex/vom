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

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class DependencyInDenormalizer
{
    public bool $example;
    public string $type;
    public string $format;

    #[VOM\Denormalizer]
    public function denormalizeData(
        #[VOM\Argument]
        string $type,
        ParameterBagInterface $parameterBag,
        #[VOM\Argument]
        ?string $format,
    ): void {
        $this->example = $parameterBag->get('example');
        $this->type = $type;
        $this->format = $format;
    }
}
