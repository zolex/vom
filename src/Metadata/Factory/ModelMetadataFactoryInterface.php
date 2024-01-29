<?php

namespace Zolex\VOM\Metadata\Factory;

use Zolex\VOM\Metadata\ModelMetadata;
use Zolex\VOM\Metadata\PropertyMetadata;

interface ModelMetadataFactoryInterface
{
    public function create(string $class, ?PropertyMetadata $parentPropertyMetadata = null): ModelMetadata;
}
