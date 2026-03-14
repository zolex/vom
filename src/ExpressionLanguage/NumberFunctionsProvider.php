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

namespace Zolex\VOM\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class NumberFunctionsProvider implements ExpressionFunctionProviderInterface
{
    private const FUNCTIONS = [
        'abs', 'ceil', 'floor', 'round', 'min', 'max',
        'pow', 'sqrt', 'intdiv', 'fmod',
        'intval', 'floatval',
    ];

    public function getFunctions(): array
    {
        return array_map(ExpressionFunction::fromPhp(...), self::FUNCTIONS);
    }
}
