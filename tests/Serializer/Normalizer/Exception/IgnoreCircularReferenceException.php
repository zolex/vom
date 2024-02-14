<?php

/*
 * This file is part of the VOM package.
 *
 * (c) Andreas Linden <zlx@gmx.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zolex\VOM\Test\Serializer\Normalizer\Exception;

use Zolex\VOM\Exception\ExceptionInterface;
use Zolex\VOM\Exception\RuntimeException;

class IgnoreCircularReferenceException extends RuntimeException implements ExceptionInterface
{
}
