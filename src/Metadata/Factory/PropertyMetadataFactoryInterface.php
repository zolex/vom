<?php

namespace Zolex\VOM\Metadata\Factory;

use Zolex\VOM\Metadata\PropertyMetadata;

interface PropertyMetadataFactoryInterface
{
    public function create(
        \ReflectionProperty $reflectionProperty,
        \ReflectionClass $reflectionClass,
    ): ?PropertyMetadata;
}
