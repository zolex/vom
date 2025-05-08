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

use Zolex\VOM\Mapping\Model;
use Zolex\VOM\Mapping\Normalizer;

#[Model]
class ScenarioNormalizer
{
    #[Normalizer(scenario: 'one')]
    public function normalizeOne(): array
    {
        return [
            'scenario' => 'one',
            'additional' => 1,
        ];
    }

    #[Normalizer(accessor: '[scenario]', scenario: 'two')]
    public function normalizeTwoScenario(): string
    {
        return 'two';
    }

    #[Normalizer(accessor: '[additional]', scenario: 'two')]
    public function normalizeTwoAdditional(): int
    {
        return 2;
    }
}
