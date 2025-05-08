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

use Zolex\VOM\Mapping\Argument;
use Zolex\VOM\Mapping\Model;

#[Model]
class ScenarioConstructorArguments
{
    private string $firstName;
    private string $lastName;

    public function __construct(
        #[Argument('[firstname]', scenario: 'one')]
        #[Argument('[name][firstname]', scenario: 'two')]
        string $firstName,
        #[Argument('[lastname]', scenario: 'one')]
        #[Argument('[name][surname]', scenario: 'two')]
        string $lastName,
    ) {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }
}
