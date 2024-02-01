<?php

namespace Zolex\VOM\Symfony\PropertyInfo;

use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;

class PropertyInfoExtractorFactory
{
    public static function create(): PropertyInfoExtractorInterface
    {
        $phpDocExtractor = new PhpDocExtractor();
        $reflectionExtractor = new ReflectionExtractor();

        return new PropertyInfoExtractor(
            //listExtractors: [$reflectionExtractor],
            typeExtractors: [$phpDocExtractor, $reflectionExtractor],
            //descriptionExtractors: [$phpDocExtractor]
            //accessExtractors: [$reflectionExtractor],
            //propertyInitializableExtractors: [$reflectionExtractor],
        );
    }
}
