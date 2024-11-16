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

namespace CmsIg\Seal\Integration\Symfony;

use CmsIg\Seal\Adapter\AdapterInterface;
use CmsIg\Seal\Adapter\Multi\MultiAdapterFactory;
use CmsIg\Seal\Adapter\ReadWrite\ReadWriteAdapterFactory;
use CmsIg\Seal\Engine;
use CmsIg\Seal\EngineInterface;
use CmsIg\Seal\Reindex\ReindexProviderInterface;
use CmsIg\Seal\Schema\Loader\PhpFileLoader;
use CmsIg\Seal\Schema\Schema;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * @experimental
 */
final class SealBundle extends AbstractBundle
{
    protected string $extensionAlias = 'cmsig_seal';

    public function configure(DefinitionConfigurator $definition): void
    {
        // @phpstan-ignore-next-line
        $definition->rootNode()
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
    }

    /**
     * @param array{
     *     index_name_prefix: string,
     *     engines: array<string, array{adapter: string}>,
     *     schemas: array<string, array{dir: string, engine?: string}>,
     * } $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $indexNamePrefix = $config['index_name_prefix'];
        $engines = $config['engines'];
        $schemas = $config['schemas'];

        $engineSchemaDirs = [];
        foreach ($schemas as $options) {
            $engineSchemaDirs[$options['engine'] ?? 'default'][] = $options['dir'];
        }

        foreach ($engines as $name => $engineConfig) {
            $adapterServiceId = 'cmsig_seal.adapter.' . $name;
            $engineServiceId = 'cmsig_seal.engine.' . $name;
            $schemaLoaderServiceId = 'cmsig_seal.schema_loader.' . $name;
            $schemaId = 'cmsig_seal.schema.' . $name;

            $definition = $builder->register($adapterServiceId, AdapterInterface::class)
                ->setFactory([new Reference('cmsig_seal.adapter_factory'), 'createAdapter'])
                ->setArguments([$engineConfig['adapter']])
                ->addTag('cmsig_seal.adapter', ['name' => $name]);

            if (\class_exists(ReadWriteAdapterFactory::class) || \class_exists(MultiAdapterFactory::class)) {
                // the read-write and multi adapter require access all other adapters so they need to be public
                $definition->setPublic(true);
            }

            $dirs = $engineSchemaDirs[$name] ?? [];

            $builder->register($schemaLoaderServiceId, PhpFileLoader::class)
                ->setArguments([$dirs, $indexNamePrefix]);

            $builder->register($schemaId, Schema::class)
                ->setFactory([new Reference($schemaLoaderServiceId), 'load']);

            $builder->register($engineServiceId, Engine::class)
                ->setArguments([
                    new Reference($adapterServiceId),
                    new Reference($schemaId),
                ])
                ->addTag('cmsig_seal.engine', ['name' => $name]);

            if ('default' === $name || (!isset($engines['default']) && !$builder->has(EngineInterface::class))) {
                $builder->setAlias(EngineInterface::class, $engineServiceId);
                $builder->setAlias(Schema::class, $schemaId);
            }

            $builder->registerAliasForArgument(
                $engineServiceId,
                EngineInterface::class,
                $name . 'Engine',
            );

            $builder->registerAliasForArgument(
                $schemaId,
                Schema::class,
                $name . 'Schema',
            );
        }

        $builder->registerForAutoconfiguration(ReindexProviderInterface::class)
            ->addTag('cmsig_seal.reindex_provider');

        $container->import(\dirname(__DIR__) . '/config/services.php');
    }
}
