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

namespace CmsIg\Seal\Integration\Laravel;

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
use CmsIg\Seal\Integration\Laravel\Console\IndexCreateCommand;
use CmsIg\Seal\Integration\Laravel\Console\IndexDropCommand;
use CmsIg\Seal\Integration\Laravel\Console\ReindexCommand;
use CmsIg\Seal\Schema\Loader\LoaderInterface;
use CmsIg\Seal\Schema\Loader\PhpFileLoader;
use CmsIg\Seal\Schema\Schema;
use Illuminate\Support\ServiceProvider;

/**
 * @experimental
 */
final class SealProvider extends ServiceProvider
{
    /**
     * @internal
     */
    public function register(): void
    {
        $this->publishes([
            \dirname(__DIR__) . '/config/cmsig_seal.php' => config_path('cmsig_seal.php'),
        ]);

        $this->mergeConfigFrom(\dirname(__DIR__) . '/config/cmsig_seal.php', 'cmsig_seal');
    }

    /**
     * @internal
     */
    public function boot(): void
    {
        $this->commands([
            IndexCreateCommand::class,
            IndexDropCommand::class,
            ReindexCommand::class,
        ]);

        /** @var array{cmsig_seal: mixed[]} $globalConfig */
        $globalConfig = $this->app->get('config');

        /**
         * @var array{
         *     index_name_prefix: string,
         *     engines: array<string, array{adapter: string}>,
         *     schemas: array<string, array{dir: string, engine?: string}>,
         * } $config
         */
        $config = $globalConfig['cmsig_seal'];
        $indexNamePrefix = $config['index_name_prefix'];
        $engines = $config['engines'];
        $schemas = $config['schemas'];

        $engineSchemaDirs = [];
        foreach ($schemas as $options) {
            $engineSchemaDirs[$options['engine'] ?? 'default'][] = $options['dir'];
        }

        $this->createAdapterFactories();
        $engineServices = [];

        foreach ($engines as $name => $engineConfig) {
            $adapterServiceId = 'cmsig_seal.adapter.' . $name;
            $engineServiceId = 'cmsig_seal.engine.' . $name;
            $schemaLoaderServiceId = 'cmsig_seal.schema_loader.' . $name;
            $schemaId = 'cmsig_seal.schema.' . $name;

            /** @var string $adapterDsn */
            $adapterDsn = $engineConfig['adapter'];
            $dirs = $engineSchemaDirs[$name] ?? [];

            $this->app->singleton($adapterServiceId, function ($app) use ($adapterDsn) {
                /** @var AdapterFactory $factory */
                $factory = $app['cmsig_seal.adapter_factory'];

                return $factory->createAdapter($adapterDsn);
            });

            $this->app->singleton($schemaLoaderServiceId, fn () => new PhpFileLoader($dirs, $indexNamePrefix));

            $this->app->singleton($schemaId, function ($app) use ($schemaLoaderServiceId) {
                /** @var LoaderInterface $loader */
                $loader = $app[$schemaLoaderServiceId];

                return $loader->load();
            });

            $engineServices[$name] = $engineServiceId;
            $this->app->singleton($engineServiceId, function ($app) use ($adapterServiceId, $schemaId) {
                /** @var AdapterInterface $adapter */
                $adapter = $app->get($adapterServiceId);
                /** @var Schema $schema */
                $schema = $app->get($schemaId);

                return new Engine($adapter, $schema);
            });

            if ('default' === $name || (!isset($engines['default']) && !$this->app->has(EngineInterface::class))) {
                $this->app->alias($engineServiceId, EngineInterface::class);
                $this->app->alias($schemaId, Schema::class);
            }
        }

        $this->app->singleton('cmsig_seal.engine_factory', function ($app) use ($engineServices) {
            $engines = []; // TODO use tagged like in adapter factories
            foreach ($engineServices as $name => $engineServiceId) {
                $engines[$name] = $app->get($engineServiceId);
            }

            return new EngineRegistry($engines);
        });

        $this->app->alias('cmsig_seal.engine_factory', EngineRegistry::class);

        $this->app->when(ReindexCommand::class)
            ->needs('$reindexProviders')
            ->giveTagged('cmsig_seal.reindex_provider');

        $this->app->tagged('cmsig_seal.reindex_provider');
    }

    private function createAdapterFactories(): void
    {
        $this->app->singleton('cmsig_seal.adapter_factory', function ($app) {
            $factories = [];
            /** @var AdapterFactoryInterface $service */
            foreach ($app->tagged('cmsig_seal.adapter_factory') as $service) {
                $factories[$service::getName()] = $service;
            }

            return new AdapterFactory($factories);
        });

        if (\class_exists(AlgoliaAdapterFactory::class)) {
            $this->app->singleton('cmsig_seal.algolia.adapter_factory', fn ($app) => new AlgoliaAdapterFactory($app));

            $this->app->tag(
                'cmsig_seal.algolia.adapter_factory',
                'cmsig_seal.adapter_factory',
            );
        }

        if (\class_exists(ElasticsearchAdapterFactory::class)) {
            $this->app->singleton('cmsig_seal.elasticsearch.adapter_factory', fn ($app) => new ElasticsearchAdapterFactory($app));

            $this->app->tag(
                'cmsig_seal.elasticsearch.adapter_factory',
                'cmsig_seal.adapter_factory',
            );
        }

        if (\class_exists(LoupeAdapterFactory::class)) {
            $this->app->singleton('cmsig_seal.loupe.adapter_factory', fn ($app) => new LoupeAdapterFactory($app));

            $this->app->tag(
                'cmsig_seal.loupe.adapter_factory',
                'cmsig_seal.adapter_factory',
            );
        }

        if (\class_exists(OpensearchAdapterFactory::class)) {
            $this->app->singleton('cmsig_seal.opensearch.adapter_factory', fn ($app) => new OpensearchAdapterFactory($app));

            $this->app->tag(
                'cmsig_seal.opensearch.adapter_factory',
                'cmsig_seal.adapter_factory',
            );
        }

        if (\class_exists(MeilisearchAdapterFactory::class)) {
            $this->app->singleton('cmsig_seal.meilisearch.adapter_factory', fn ($app) => new MeilisearchAdapterFactory($app));

            $this->app->tag(
                'cmsig_seal.meilisearch.adapter_factory',
                'cmsig_seal.adapter_factory',
            );
        }

        if (\class_exists(MemoryAdapterFactory::class)) {
            $this->app->singleton('cmsig_seal.memory.adapter_factory', fn () => new MemoryAdapterFactory());

            $this->app->tag(
                'cmsig_seal.memory.adapter_factory',
                'cmsig_seal.adapter_factory',
            );
        }

        if (\class_exists(RediSearchAdapterFactory::class)) {
            $this->app->singleton('cmsig_seal.redis.adapter_factory', fn ($app) => new RediSearchAdapterFactory($app));

            $this->app->tag(
                'cmsig_seal.redis.adapter_factory',
                'cmsig_seal.adapter_factory',
            );
        }

        if (\class_exists(SolrAdapterFactory::class)) {
            $this->app->singleton('cmsig_seal.solr.adapter_factory', fn ($app) => new SolrAdapterFactory($app));

            $this->app->tag(
                'cmsig_seal.solr.adapter_factory',
                'cmsig_seal.adapter_factory',
            );
        }

        if (\class_exists(TypesenseAdapterFactory::class)) {
            $this->app->singleton('cmsig_seal.typesense.adapter_factory', fn ($app) => new TypesenseAdapterFactory($app));

            $this->app->tag(
                'cmsig_seal.typesense.adapter_factory',
                'cmsig_seal.adapter_factory',
            );
        }

        // ...

        if (\class_exists(ReadWriteAdapterFactory::class)) {
            $this->app->singleton('cmsig_seal.read_write.adapter_factory', fn ($app) => new ReadWriteAdapterFactory(
                $app,
                'cmsig_seal.adapter.',
            ));

            $this->app->tag(
                'cmsig_seal.read_write.adapter_factory',
                'cmsig_seal.adapter_factory',
            );
        }

        if (\class_exists(MultiAdapterFactory::class)) {
            $this->app->singleton('cmsig_seal.multi.adapter_factory', fn ($app) => new MultiAdapterFactory(
                $app,
                'cmsig_seal.adapter.',
            ));

            $this->app->tag(
                'cmsig_seal.multi.adapter_factory',
                'cmsig_seal.adapter_factory',
            );
        }
    }
}
