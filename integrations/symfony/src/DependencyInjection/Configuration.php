<?php

declare(strict_types=1);

/*
 * This file is part of the CMS-IG SEAL project.
 *
 * (c) Alexander Schranz <alexander@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CmsIg\Seal\Integration\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @experimental
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('cmsig_seal');
        $rootNode = $treeBuilder->getRootNode();

        // @phpstan-ignore-next-line
        $rootNode
            ->children()
                ->scalarNode('index_name_prefix')->defaultValue('')->end()
                    ->arrayNode('schemas')
                        ->useAttributeAsKey('name')
                            ->arrayPrototype()
                            ->children()
                                ->scalarNode('dir')->end()
                                ->scalarNode('engine')->defaultNull()->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('engines')
                            ->useAttributeAsKey('name')
                            ->arrayPrototype()
                            ->children()
                                ->scalarNode('adapter')->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
