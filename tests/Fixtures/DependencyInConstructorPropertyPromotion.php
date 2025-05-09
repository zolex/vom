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
class DependencyInConstructorPropertyPromotion
{
    public function __construct(
        #[VOM\Argument]
        public string $type,
        private ParameterBagInterface $parameterBag,
        #[VOM\Argument]
        public ?string $format,
    ) {
    }

    public function getExample(): bool
    {
        return $this->parameterBag->get('example');
    }
}
