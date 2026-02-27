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

require dirname(__DIR__).'/vendor/autoload.php';

// workaround for PHPUnit 12 and Symfony's Error handler
// @see https://github.com/symfony/symfony/issues/53812#issuecomment-1962311843
use Symfony\Component\ErrorHandler\ErrorHandler;

ErrorHandler::register(null, false);

// Suppress VOM's own deprecation warnings during tests by wrapping trigger_error calls
// This preserves other deprecation warnings while filtering out our custom ones
if (!function_exists('vom_trigger_deprecation')) {
    function vom_trigger_deprecation(string $message): void
    {
        // Set error handler to suppress this specific deprecation
        $handler = set_error_handler(static function (): bool {
            restore_error_handler();

            return true;
        }, \E_USER_DEPRECATED);

        @trigger_error($message, \E_USER_DEPRECATED);
        restore_error_handler();
    }
}
