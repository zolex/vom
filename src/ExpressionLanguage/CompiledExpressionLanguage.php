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

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Extends ExpressionLanguage to compile expressions to PHP code on first use
 * and evaluate them via eval() on subsequent calls, avoiding AST interpretation overhead.
 */
class CompiledExpressionLanguage extends ExpressionLanguage
{
    /** @var array<string, string> in-memory cache of compiled PHP code strings */
    private array $compiled = [];

    public function evaluate(Expression|string $expression, array $values = []): mixed
    {
        $names = array_keys($values);
        sort($names);
        $cacheKey = ((string) $expression).'|'.implode(',', $names);

        if (!isset($this->compiled[$cacheKey])) {
            $this->compiled[$cacheKey] = $this->compile($expression, $names);
        }

        extract($values, \EXTR_SKIP);

        return eval('return '.$this->compiled[$cacheKey].';');
    }
}
