<?php

namespace Zolex\VOM\Metadata\Factory;

use Zolex\VOM\Metadata\ModelMetadata;

interface ModelMetadataFactoryInterface
{
    public function create(string $class): ?ModelMetadata;
}
