<?php

/*
 * This file is part of the VOM package.
 *
 * (c) Andreas Linden <zlx@gmx.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zolex\VOM\Test\Symfony\Bundle;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Serializer\SerializerInterface;
use Zolex\VOM\Metadata\Factory\ModelMetadataFactoryInterface;
use Zolex\VOM\PropertyInfo\Extractor\MethodCallExtractor;
use Zolex\VOM\Serializer\Normalizer\ObjectNormalizer;
use Zolex\VOM\Serializer\VersatileObjectMapper;
use Zolex\VOM\Symfony\Bundle\ZolexVOMBundle;

class ZolexVOMTestKernel extends Kernel implements CompilerPassInterface
{
    use MicroKernelTrait;

    public const SERVICES = [
        'zolex_vom.metadata.model_metadata_factory' => ModelMetadataFactoryInterface::class,
        'zolex_vom.serializer.versatile_object_mapper' => VersatileObjectMapper::class,
        'zolex_vom.serializer.normalizer.object_normalizer' => ObjectNormalizer::class,
        'zolex_vom.property_info.method_call_extractor' => MethodCallExtractor::class,
        'serializer' => SerializerInterface::class,
    ];

    public const NON_DEBUG_SERVICES = [
        'zolex_vom.metadata.model_metadata_factory.cached' => ModelMetadataFactoryInterface::class,
    ];

    /**
     * make services public so we can test them.
     */
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->getDefinitions() as $id => $definition) {
            if (\array_key_exists($id, self::SERVICES) || \array_key_exists($id, self::NON_DEBUG_SERVICES)) {
                $definition->setPublic(true);
            }
        }
    }

    public function getVarDir(): string
    {
        return sys_get_temp_dir().'/zolex-vom-bundle/var';
    }

    public function getCacheDir(): string
    {
        return $this->getVarDir().'/cache';
    }

    public function getLogDir(): string
    {
        return $this->getVarDir().'/log';
    }

    public function getProjectDir(): string
    {
        return realpath('./tests/Symfony/Bundle');
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new ZolexVOMBundle();
    }

    public function shutdown(): void
    {
        parent::shutdown();

        $filesystem = new Filesystem();
        $varDir = $this->getVarDir();
        if ($filesystem->exists($varDir)) {
            $filesystem->remove($varDir);
        }
    }
}
