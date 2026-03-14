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

class ArrayFunctionsProvider implements ExpressionFunctionProviderInterface
{
    private const FUNCTIONS = [
        'count', 'in_array', 'array_key_exists', 'array_keys', 'array_values',
        'array_search', 'array_column', 'array_flip', 'array_reverse', 'array_unique',
        'array_merge', 'array_replace', 'array_combine', 'array_diff', 'array_intersect',
        'array_slice', 'array_chunk', 'array_fill', 'array_fill_keys', 'range',
        'array_map', 'array_filter', 'array_reduce',
        'array_sum', 'array_product', 'array_count_values',
        'sort', 'rsort', 'asort', 'arsort', 'ksort', 'krsort', 'usort', 'uasort', 'uksort',
    ];

    public function getFunctions(): array
    {
        return array_map(ExpressionFunction::fromPhp(...), self::FUNCTIONS);
    }
}
