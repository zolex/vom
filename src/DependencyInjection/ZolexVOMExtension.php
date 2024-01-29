<?php

declare(strict_types=1);

namespace Zolex\VOM\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class ZolexVOMExtension extends Extension
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
}
