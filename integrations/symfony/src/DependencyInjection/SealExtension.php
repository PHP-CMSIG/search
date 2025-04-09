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

use CmsIg\Seal\Adapter\AdapterInterface;
use CmsIg\Seal\Adapter\Multi\MultiAdapterFactory;
use CmsIg\Seal\Adapter\ReadWrite\ReadWriteAdapterFactory;
use CmsIg\Seal\Engine;
use CmsIg\Seal\EngineInterface;
use CmsIg\Seal\Reindex\ReindexProviderInterface;
use CmsIg\Seal\Schema\Loader\PhpFileLoader as SealPhpFileLoader;
use CmsIg\Seal\Schema\Schema;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @experimental
 */
final class SealExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new PhpFileLoader($container, new FileLocator(\dirname(__DIR__) . '/../config'));
        $loader->load('services.php');

        $configuration = new Configuration();

        /**
         * @var array{
         *     index_name_prefix: string,
         *     engines: array<string, array{adapter: string}>,
         *     schemas: array<string, array{dir: string, engine?: string}>,
         * } $config
         */
        $config = $this->processConfiguration($configuration, $configs);

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

            $definition = $container->register($adapterServiceId, AdapterInterface::class)
                ->setFactory([new Reference('cmsig_seal.adapter_factory'), 'createAdapter'])
                ->setArguments([$engineConfig['adapter']])
                ->addTag('cmsig_seal.adapter', ['name' => $name]);

            if (\class_exists(ReadWriteAdapterFactory::class) || \class_exists(MultiAdapterFactory::class)) {
                // the read-write and multi adapter require access all other adapters so they need to be public
                $definition->setPublic(true);
            }

            $dirs = $engineSchemaDirs[$name] ?? [];

            $container->register($schemaLoaderServiceId, SealPhpFileLoader::class)
                ->setArguments([$dirs, $indexNamePrefix]);

            $container->register($schemaId, Schema::class)
                ->setFactory([new Reference($schemaLoaderServiceId), 'load']);

            $container->register($engineServiceId, Engine::class)
                ->setArguments([
                    new Reference($adapterServiceId),
                    new Reference($schemaId),
                ])
                ->addTag('cmsig_seal.engine', ['name' => $name]);

            if ('default' === $name || (!isset($engines['default']) && !$container->has(EngineInterface::class))) {
                $container->setAlias(EngineInterface::class, $engineServiceId);
                $container->setAlias(Schema::class, $schemaId);
            }

            $container->registerAliasForArgument(
                $engineServiceId,
                EngineInterface::class,
                $name . 'Engine',
            );

            $container->registerAliasForArgument(
                $schemaId,
                Schema::class,
                $name . 'Schema',
            );
        }

        $container->registerForAutoconfiguration(ReindexProviderInterface::class)
            ->addTag('cmsig_seal.reindex_provider');
    }

    public function getAlias(): string
    {
        return 'cmsig_seal';
    }
}
