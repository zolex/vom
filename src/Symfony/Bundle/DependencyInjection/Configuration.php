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

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration.
 *
 * @codeCoverageIgnore
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('zolex_vom');
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('method_dependencies')
                    ->scalarPrototype()->end()
                ->end()

                // deprecated. to be removed in future release
                ->arrayNode('denormalizer')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('dependencies')
                        ->scalarPrototype()->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
