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
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class ZolexVOMExtension extends Extension implements CompilerPassInterface
{
    private array $config;

    /**
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.php');

        $configuration = $this->getConfiguration($configs, $container);
        $this->config = $this->processConfiguration($configuration, $configs);
    }

    public function process(ContainerBuilder $container): void
    {
        $factory = $container->getDefinition('zolex_vom.metadata.model_metadata_factory');

        if (\count($this->config['denormalizer']['dependencies'])) {
            $message = 'The config key "denormalizer.dependencies" is deprecated. Use method_dependencies instead.';
            if (\function_exists('vom_trigger_deprecation')) {
                vom_trigger_deprecation($message);
            } else {
                @trigger_error($message, \E_USER_DEPRECATED);
            }
        }

        if (\count($this->config['method_dependencies'])) {
            $message = 'The method_dependencies configuration is deprecated and will be removed in VOM 3.0.';
            if (\function_exists('vom_trigger_deprecation')) {
                vom_trigger_deprecation($message);
            } else {
                @trigger_error($message, \E_USER_DEPRECATED);
            }
        }

        foreach (array_merge($this->config['method_dependencies'], $this->config['denormalizer']['dependencies']) as $service) {
            $service = ltrim($service, '@');
            if (!$container->hasDefinition($service)) {
                throw new InvalidArgumentException('Method dependency not found in container: '.$service);
            }

            $serviceDefinition = $container->getDefinition($service);
            $factory->addMethodCall('injectMethodDependency', [$serviceDefinition]);
        }

        if ($container->getParameter('kernel.debug')) {
            $container->removeDefinition('zolex_vom.metadata.model_metadata_factory.cached');
        }
    }
}
