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

use Zolex\VOM\Metadata\Factory\ModelMetadataFactory;

/**
 * Technically allow this on parameters and properties, so it can be used on constructor
 * arguments with property promotion. ModelMetadataFactory will throw a php-like
 * exception, if the Argument is used on an actual property outside the constructor.
 *
 * {@see ModelMetadataFactory::createPropertyMetadata()}
 */
#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Argument extends AbstractProperty
{
}
