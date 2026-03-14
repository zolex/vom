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

class StringFunctionsProvider implements ExpressionFunctionProviderInterface
{
    private const FUNCTIONS = [
        'strlen', 'substr', 'str_contains', 'str_starts_with', 'str_ends_with',
        'str_replace', 'str_ireplace', 'str_pad', 'str_repeat', 'str_split',
        'strtolower', 'strtoupper', 'lcfirst', 'ucfirst', 'ucwords',
        'trim', 'ltrim', 'rtrim', 'strpos', 'stripos',
        'base64_encode', 'base64_decode', 'urlencode', 'urldecode', 'rawurlencode', 'rawurldecode',
        'number_format', 'sprintf', 'explode', 'implode',
        'preg_match', 'preg_replace', 'preg_split',
        'mb_strlen', 'mb_substr', 'mb_strtolower', 'mb_strtoupper', 'mb_strpos',
        'mb_str_split', 'mb_convert_encoding', 'mb_convert_case',
    ];

    public function getFunctions(): array
    {
        return array_map(ExpressionFunction::fromPhp(...), self::FUNCTIONS);
    }
}
