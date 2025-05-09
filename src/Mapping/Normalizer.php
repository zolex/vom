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

namespace Zolex\VOM\Mapping;

use Zolex\VOM\Metadata\ModelMetadata;

#[\Attribute(\Attribute::TARGET_METHOD)]
final class Normalizer
{
    public function __construct(
        private readonly ?string $accessor = null,
        private readonly ?string $scenario = ModelMetadata::DEFAULT_SCENARIO,
    ) {
    }

    public function getAccessor(): ?string
    {
        return $this->accessor;
    }

    public function getScenario(): ?string
    {
        return $this->scenario;
    }
}
