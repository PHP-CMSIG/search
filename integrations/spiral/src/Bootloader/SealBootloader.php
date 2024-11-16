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

namespace CmsIg\Seal\Integration\Spiral\Bootloader;

use CmsIg\Seal\Adapter\AdapterFactory;
use CmsIg\Seal\Adapter\AdapterFactoryInterface;
use CmsIg\Seal\Adapter\AdapterInterface;
use CmsIg\Seal\Adapter\Algolia\AlgoliaAdapterFactory;
use CmsIg\Seal\Adapter\Elasticsearch\ElasticsearchAdapterFactory;
use CmsIg\Seal\Adapter\Loupe\LoupeAdapterFactory;
use CmsIg\Seal\Adapter\Meilisearch\MeilisearchAdapterFactory;
use CmsIg\Seal\Adapter\Memory\MemoryAdapterFactory;
use CmsIg\Seal\Adapter\Multi\MultiAdapterFactory;
use CmsIg\Seal\Adapter\Opensearch\OpensearchAdapterFactory;
use CmsIg\Seal\Adapter\ReadWrite\ReadWriteAdapterFactory;
use CmsIg\Seal\Adapter\RediSearch\RediSearchAdapterFactory;
use CmsIg\Seal\Adapter\Solr\SolrAdapterFactory;
use CmsIg\Seal\Adapter\Typesense\TypesenseAdapterFactory;
use CmsIg\Seal\Engine;
use CmsIg\Seal\EngineInterface;
use CmsIg\Seal\EngineRegistry;
use CmsIg\Seal\Integration\Spiral\Config\SealConfig;
use CmsIg\Seal\Integration\Spiral\Console\IndexCreateCommand;
use CmsIg\Seal\Integration\Spiral\Console\IndexDropCommand;
use CmsIg\Seal\Integration\Spiral\Console\ReindexCommand;
use CmsIg\Seal\Reindex\ReindexProviderInterface;
use CmsIg\Seal\Schema\Loader\LoaderInterface;
use CmsIg\Seal\Schema\Loader\PhpFileLoader;
use CmsIg\Seal\Schema\Schema;
use Spiral\Boot\Bootloader\Bootloader;
use Spiral\Boot\DirectoriesInterface;
use Spiral\Boot\EnvironmentInterface;
use Spiral\Config\ConfiguratorInterface;
use Spiral\Console\Bootloader\ConsoleBootloader;
use Spiral\Core\Container;

/**
 * @experimental
 */
final class SealBootloader extends Bootloader
{
    private const ADAPTER_FACTORIES = [
        AlgoliaAdapterFactory::class,
        ElasticsearchAdapterFactory::class,
        LoupeAdapterFactory::class,
        OpensearchAdapterFactory::class,
        MeilisearchAdapterFactory::class,
        MemoryAdapterFactory::class,
        RediSearchAdapterFactory::class,
        SolrAdapterFactory::class,
        TypesenseAdapterFactory::class,
    ];

    /**
     * @param ConfiguratorInterface<SealConfig> $config
     */
    public function __construct(
        private readonly ConfiguratorInterface $config,
    ) {
    }

    public function init(
        ConsoleBootloader $console,
        DirectoriesInterface $dirs,
        EnvironmentInterface $environment,
    ): void {
        $console->addCommand(IndexCreateCommand::class);
        $console->addCommand(IndexDropCommand::class);
        $console->addCommand(ReindexCommand::class);

        $this->config->setDefaults(
            SealConfig::CONFIG,
            [
                'index_name_prefix' => $environment->get('SEAL_SEARCH_PREFIX', ''),
                'schemas' => [
                    'app' => [
                        'dir' => $dirs->get('app') . 'schemas',
                    ],
                ],
                'engines' => [],
            ],
        );
    }

    public function boot(Container $container, SealConfig $config): void
    {
        $this->createAdapterFactories($container);

        $engineSchemaDirs = [];
        foreach ($config->getSchemas() as $options) {
            $engineSchemaDirs[$options['engine'] ?? 'default'][] = $options['dir'];
        }

        $engines = $config->getEngines();

        $engineServices = [];
        foreach ($engines as $name => $engineConfig) {
            $adapterServiceId = 'cmsig_seal.adapter.' . $name;
            $engineServiceId = 'cmsig_seal.engine.' . $name;
            $schemaLoaderServiceId = 'cmsig_seal.schema_loader.' . $name;
            $schemaId = 'cmsig_seal.schema.' . $name;

            /** @var string $adapterDsn */
            $adapterDsn = $engineConfig['adapter'] ?? throw new \RuntimeException(\sprintf(
                'No adapter DSN configured for engine "%s".',
                $name,
            ));
            $dirs = $engineSchemaDirs[$name] ?? [];

            $container->bindSingleton(
                $adapterServiceId,
                static fn (AdapterFactory $factory): AdapterInterface => $factory->createAdapter($adapterDsn),
            );

            $container->bindSingleton(
                $schemaLoaderServiceId,
                static fn (Container $container): PhpFileLoader => new PhpFileLoader($dirs, $config->getIndexNamePrefix()),
            );

            $container->bindSingleton(
                $schemaId,
                static function (Container $container) use ($schemaLoaderServiceId): Schema {
                    /** @var LoaderInterface $loader */
                    $loader = $container->get($schemaLoaderServiceId);

                    return $loader->load();
                },
            );

            $engineServices[$name] = $engineServiceId;
            $container->bindSingleton(
                $engineServiceId,
                static function (Container $container) use ($adapterServiceId, $schemaId): EngineInterface {
                    /** @var AdapterInterface $adapter */
                    $adapter = $container->get($adapterServiceId);
                    /** @var Schema $schema */
                    $schema = $container->get($schemaId);

                    return new Engine($adapter, $schema);
                },
            );

            if ('default' === $name || (!isset($engines['default']) && !$container->has(EngineInterface::class))) {
                $container->bind(EngineInterface::class, $engineServiceId);
                $container->bind(Schema::class, $schemaId);
            }
        }

        $container->bindSingleton(
            EngineRegistry::class,
            static function (Container $container) use ($engineServices): EngineRegistry {
                $engines = [];

                foreach ($engineServices as $name => $engineServiceId) {
                    /** @var EngineInterface $engine */
                    $engine = $container->get($engineServiceId);

                    $engines[$name] = $engine;
                }

                return new EngineRegistry($engines);
            },
        );

        $reindexProviderNames = $config->getReindexProviders(); // TODO tagged services would make this easier

        $container->bindSingleton(
            ReindexCommand::class,
            static function (Container $container) use ($reindexProviderNames): ReindexCommand {
                $reindexProviders = [];
                foreach ($reindexProviderNames as $reindexProviderName) {
                    $reindexProvider = $container->get($reindexProviderName);

                    if (!$reindexProvider instanceof ReindexProviderInterface) {
                        throw new \RuntimeException(\sprintf(
                            'Reindex provider "%s" does not implement "%s".',
                            $reindexProviderName,
                            ReindexProviderInterface::class,
                        ));
                    }

                    $reindexProviders[] = $reindexProvider;
                }

                return new ReindexCommand($reindexProviders);
            },
        );
    }

    private function createAdapterFactories(Container $container): void
    {
        $adapterServices = []; // TODO tagged services would make this extensible

        foreach (self::ADAPTER_FACTORIES as $adapterClass) {
            if (!\class_exists($adapterClass)) {
                continue;
            }

            $container->bindSingleton($adapterClass, $adapterClass);
            $adapterServices[$adapterClass::getName()] = $adapterClass;
        }

        // ...

        $prefix = 'cmsig_seal.adapter.';

        $wrapperAdapters = [
            ReadWriteAdapterFactory::class,
            MultiAdapterFactory::class,
        ];

        foreach ($wrapperAdapters as $adapterClass) {
            if (!\class_exists($adapterClass)) {
                continue;
            }

            $container->bindSingleton(
                $adapterClass,
                static fn (Container $container): AdapterFactoryInterface => new $adapterClass($container, $prefix),
            );

            $adapterServices[$adapterClass::getName()] = $adapterClass;
        }

        // ...

        $container->bindSingleton(
            AdapterFactory::class,
            static function (Container $container) use ($adapterServices): AdapterFactory {
                $factories = [];
                foreach ($adapterServices as $name => $adapterServiceId) {
                    /** @var AdapterFactoryInterface $adapterFactory */
                    $adapterFactory = $container->get($adapterServiceId);

                    $factories[$name] = $adapterFactory;
                }

                return new AdapterFactory($factories);
            },
        );
    }
}
