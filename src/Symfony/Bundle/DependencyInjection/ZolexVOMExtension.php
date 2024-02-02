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

namespace Zolex\VOM\Symfony\Bundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Zolex\VOM\ApiPlatform\Serializer\GroupsContextBuilder;

class ZolexVOMExtension extends Extension implements CompilerPassInterface
{
    private array $config;

    /**
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
