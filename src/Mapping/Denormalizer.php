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

#[\Attribute(\Attribute::TARGET_METHOD)]
/**
 * @deprecated Denormalizer methods are deprecated and will be removed in VOM 3.0.
 *             VOM is designed to operate without requiring custom code, and the intended behavior
 *             can be achieved using VOM's built-in features.
 *             See https://zolex.github.io/vom for guidance.
 */
final class Denormalizer
{
    public function __construct(
        private readonly bool $allowNonScalarArguments = false,
    ) {
        $message = 'Denormalizer methods are deprecated and will be removed in VOM 3.0. VOM is designed to operate without requiring custom code, and the intended behavior can be achieved using VOM\'s built-in features.\n\nPlease refer to the documentation at https://zolex.github.io/vom for guidance.\n\nIf your use case is not supported, kindly open an issue at https://github.com/zolex/vom/issues to share your requirements.';
        if (\function_exists('vom_trigger_deprecation')) {
            vom_trigger_deprecation($message);
        } else {
            @trigger_error($message, \E_USER_DEPRECATED);
        }
    }

    public function allowNonScalarArguments(): bool
    {
        return $this->allowNonScalarArguments;
    }
}
