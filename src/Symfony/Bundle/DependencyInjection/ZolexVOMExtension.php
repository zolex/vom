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
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

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
        $factory = $container->getDefinition('zolex_vom.metadata.model_metadata_factory');
        foreach ($this->config['denormalizer']['dependencies'] as $service) {
            $service = ltrim($service, '@');
            if (!$container->hasDefinition($service)) {
                throw new InvalidArgumentException('Denormalizer dependency not found in container: '.$service);
            }

            $serviceDefinition = $container->getDefinition($service);
            $factory->addMethodCall('injectDenormalizerDependency', [$serviceDefinition]);
        }

        if ($container->getParameter('kernel.debug')) {
            $container->removeDefinition('zolex_vom.metadata.model_metadata_factory.cached');
        }
    }
}
