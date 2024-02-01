<?php

namespace Zolex\VOM\Test\Mapping\Loader;

use Symfony\Component\Serializer\Mapping\ClassMetadataInterface;
use Symfony\Component\Serializer\Mapping\Loader\LoaderInterface;

class AttributeLoader implements LoaderInterface
{
    public function loadClassMetadata(ClassMetadataInterface $classMetadata): bool
    {
    }
}
