<?php

/*
 * This file is part of the VOM package.
 *
 * (c) Andreas Linden <zlx@gmx.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zolex\VOM\Metadata\Factory\Exception;

use Zolex\VOM\Exception\ExceptionInterface;
use Zolex\VOM\Metadata\Exception\RuntimeException as MetadataRuntimeException;

class MappingException extends MetadataRuntimeException implements ExceptionInterface
{
}
