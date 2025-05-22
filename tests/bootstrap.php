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
