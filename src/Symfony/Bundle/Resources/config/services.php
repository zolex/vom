<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Zolex\VOM\Metadata\Factory\CachedModelMetadataFactory;
use Zolex\VOM\Metadata\Factory\ModelMetadataFactory;
use Zolex\VOM\Metadata\Factory\ModelMetadataFactoryInterface;
use Zolex\VOM\Serializer\Normalizer\ObjectNormalizer;
use Zolex\VOM\Serializer\VersatileObjectMapper;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('zolex_vom.metadata.model_metadata_factory', ModelMetadataFactory::class)
        ->args([
            service('type_info.resolver'),
        ]);

    $services->alias(ModelMetadataFactoryInterface::class, 'zolex_vom.metadata.model_metadata_factory');

    $services->set('zolex_vom.metadata.model_metadata_factory.cached', CachedModelMetadataFactory::class)
        ->decorate('zolex_vom.metadata.model_metadata_factory', priority: -10, invalidBehavior: ContainerInterface::IGNORE_ON_INVALID_REFERENCE)
        ->public(false)
        ->args([
            service('zolex_vom.cache.metadata.model'),
            service('.inner'),
        ]);

    $services->set('zolex_vom.cache.metadata.model')
        ->parent('cache.system')
        ->public(false)
        ->tag('cache.pool');

    $services->set('zolex_vom.serializer.versatile_object_mapper', VersatileObjectMapper::class)
        ->args([
            service('serializer'),
        ]);

    $services->alias(VersatileObjectMapper::class, 'zolex_vom.serializer.versatile_object_mapper');

    $services->set('zolex_vom.serializer.normalizer.object_normalizer', ObjectNormalizer::class)
        ->args([
            service('zolex_vom.metadata.model_metadata_factory'),
            service('property_accessor'),
            service('serializer.mapping.class_metadata_factory'),
            service('serializer.mapping.class_discriminator_resolver'),
        ])
        ->tag('serializer.normalizer', ['priority' => 100]);
};
