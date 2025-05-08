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

use Symfony\Component\Serializer\Attribute\Groups;
use Zolex\VOM\Mapping\Model;
use Zolex\VOM\Mapping\Normalizer;

#[Model]
class ScenarioNormalizerWithGroups
{
    #[Groups('a')]
    #[Normalizer(scenario: 'one')]
    public function getOneA(): array
    {
        return [
            'scenario' => 'oneA',
            'additional' => 1,
        ];
    }

    #[Groups('b')]
    #[Normalizer(scenario: 'one')]
    public function getOneB(): array
    {
        return [
            'scenario' => 'oneB',
            'additional' => 1,
        ];
    }

    #[Groups('a')]
    #[Normalizer(accessor: '[scenario]', scenario: 'two')]
    public function getTwoAScenario(): string
    {
        return 'twoA';
    }

    #[Groups('a')]
    #[Normalizer(accessor: '[additional]', scenario: 'two')]
    public function getTwoAAdditional(): int
    {
        return 2;
    }

    #[Groups('b')]
    #[Normalizer(accessor: '[scenario]', scenario: 'two')]
    public function getTwoBScenario(): string
    {
        return 'twoB';
    }

    #[Groups('b')]
    #[Normalizer(accessor: '[additional]', scenario: 'two')]
    public function getTwoBAdditional(): int
    {
        return 2;
    }
}
