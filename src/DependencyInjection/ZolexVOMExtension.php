<?php

declare(strict_types=1);

namespace Zolex\VOM\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Zolex\VOM\Serializer\GroupsContextBuilder;

class ZolexVOMExtension extends Extension implements CompilerPassInterface
{
    /**
     * @var array
     */
    private array $config;

    /**
     * @param array $configs
     * @param ContainerBuilder $container
     *
     * @return void
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $configuration = $this->getConfiguration($configs, $container);
        $this->config = $this->processConfiguration($configuration, $configs);
    }

    public function process(ContainerBuilder $container): void
    {
        if ($container->has('api_platform.serializer.context_builder')) {
            $groupsContextBuilder = new Definition(GroupsContextBuilder::class);
            $groupsContextBuilder->setDecoratedService('api_platform.serializer.context_builder', null, -10);
            $groupsContextBuilder->addArgument(new Reference('zolex_vom.serializer.groups_context_builder.inner'));
            $groupsContextBuilder->addArgument(new Reference('zolex_vom.metadata.model_metadata_factory'));
            $groupsContextBuilder->addArgument('%zolex_vom.serializer.groups_query_param%');
            $container->setDefinition('zolex_vom.serializer.groups_context_builder', $groupsContextBuilder);
        }
    }
}
