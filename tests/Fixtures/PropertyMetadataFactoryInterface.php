<?php

namespace Zolex\VOM\Test\Fixtures;

use Zolex\VOM\Metadata\PropertyMetadata;

interface PropertyMetadataFactoryInterface
{
    public function create(
        \ReflectionProperty $reflectionProperty,
        \ReflectionClass $reflectionClass,
    ): ?PropertyMetadata;
}
