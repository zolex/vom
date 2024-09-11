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
class DenormalizerDependency
{
    public string $var;

    #[VOM\Denormalizer]
    public function denormalizeData(
        ParameterBagInterface $parameterBag,
    ): void {
        $this->var = $parameterBag->get('foo');
    }
}