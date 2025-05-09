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

use Psr\Log\LoggerInterface;
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class DependencyInFactoryMissing
{
    public bool $example;
    public string $type;
    public string $format;

    #[VOM\Factory]
    public static function create(
        #[VOM\Argument]
        string $type,
        LoggerInterface $logger,
        #[VOM\Argument]
        ?string $format,
    ): self {
        return new self();
    }
}
