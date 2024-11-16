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
use CmsIg\Seal\Schema\Loader\LoaderInterface;
use CmsIg\Seal\Schema\Loader\PhpFileLoader;
use CmsIg\Seal\Schema\Schema;
use Psr\Container\ContainerInterface;

/** @var \Yiisoft\Config\Config $config */
/** @var array{"cmsig/seal-yii-module": mixed[]} $params */

/**
 * @var array{
 *     index_name_prefix: string,
 *     engines: array<string, array{adapter: string}>,
 *     schemas: array<string, array{dir: string, engine?: string}>,
 * } $config
 */
$config = $params['cmsig/seal-yii-module'];
$indexNamePrefix = $config['index_name_prefix'];
$engines = $config['engines'];
$schemas = $config['schemas'];

$engineSchemaDirs = [];
foreach ($schemas as $options) {
    $engineSchemaDirs[$options['engine'] ?? 'default'][] = $options['dir'];
}

$diConfig = [];

$adapterFactories = [];

if (\class_exists(AlgoliaAdapterFactory::class)) {
    $adapterFactories['cmsig_seal.algolia.adapter_factory'] = static fn (ContainerInterface $container) => new AlgoliaAdapterFactory($container);
}

if (\class_exists(ElasticsearchAdapterFactory::class)) {
    $adapterFactories['cmsig_seal.elasticsearch.adapter_factory'] = static fn (ContainerInterface $container) => new ElasticsearchAdapterFactory($container);
}

if (\class_exists(LoupeAdapterFactory::class)) {
    $adapterFactories['cmsig_seal.loupe.adapter_factory'] = static fn (ContainerInterface $container) => new LoupeAdapterFactory($container);
}

if (\class_exists(OpensearchAdapterFactory::class)) {
    $adapterFactories['cmsig_seal.opensearch.adapter_factory'] = static fn (ContainerInterface $container) => new OpensearchAdapterFactory($container);
}

if (\class_exists(MeilisearchAdapterFactory::class)) {
    $adapterFactories['cmsig_seal.meilisearch.adapter_factory'] = static fn (ContainerInterface $container) => new MeilisearchAdapterFactory($container);
}

if (\class_exists(MemoryAdapterFactory::class)) {
    $adapterFactories['cmsig_seal.memory.adapter_factory'] = static fn (ContainerInterface $container) => new MemoryAdapterFactory();
}

if (\class_exists(RediSearchAdapterFactory::class)) {
    $adapterFactories['cmsig_seal.redis.adapter_factory'] = static fn (ContainerInterface $container) => new RediSearchAdapterFactory($container);
}

if (\class_exists(SolrAdapterFactory::class)) {
    $adapterFactories['cmsig_seal.solr.adapter_factory'] = static fn (ContainerInterface $container) => new SolrAdapterFactory($container);
}

if (\class_exists(TypesenseAdapterFactory::class)) {
    $adapterFactories['cmsig_seal.typesense.adapter_factory'] = static fn (ContainerInterface $container) => new TypesenseAdapterFactory($container);
}

// ...

if (\class_exists(ReadWriteAdapterFactory::class)) {
    $adapterFactories['cmsig_seal.read_write.adapter_factory'] = static fn (ContainerInterface $container) => new ReadWriteAdapterFactory(
        $container,
        'cmsig_seal.adapter.',
    );
}

if (\class_exists(MultiAdapterFactory::class)) {
    $adapterFactories['cmsig_seal.multi.adapter_factory'] = static fn (ContainerInterface $container) => new MultiAdapterFactory(
        $container,
        'cmsig_seal.adapter.',
    );
}

$diConfig = [...$diConfig, ...$adapterFactories];
$adapterFactoryNames = \array_keys($adapterFactories);

$diConfig['cmsig_seal.adapter_factory'] = static function (ContainerInterface $container) use ($adapterFactoryNames) {
    $factories = [];
    foreach ($adapterFactoryNames as $serviceName) {
        /** @var AdapterFactoryInterface $service */
        $service = $container->get($serviceName);

        $factories[$service::getName()] = $service;
    }

    return new AdapterFactory($factories);
};

$diConfig[AdapterFactory::class] = 'cmsig_seal.adapter_factory';

$engineServices = [];

foreach ($engines as $name => $engineConfig) {
    $adapterServiceId = 'cmsig_seal.adapter.' . $name;
    $engineServiceId = 'cmsig_seal.engine.' . $name;
    $schemaLoaderServiceId = 'cmsig_seal.schema_loader.' . $name;
    $schemaId = 'cmsig_seal.schema.' . $name;

    /** @var string $adapterDsn */
    $adapterDsn = $engineConfig['adapter'];
    $dirs = $engineSchemaDirs[$name] ?? [];

    $diConfig[$adapterServiceId] = static function (ContainerInterface $container) use ($adapterDsn) {
        /** @var AdapterFactory $factory */
        $factory = $container->get('cmsig_seal.adapter_factory');

        return $factory->createAdapter($adapterDsn);
    };

    $diConfig[$schemaLoaderServiceId] = static fn (ContainerInterface $container) => new PhpFileLoader($dirs, $indexNamePrefix);

    $diConfig[$schemaId] = static function (ContainerInterface $container) use ($schemaLoaderServiceId) {
        /** @var LoaderInterface $loader */
        $loader = $container->get($schemaLoaderServiceId);

        return $loader->load();
    };

    $engineServices[$name] = $engineServiceId;

    $diConfig[$engineServiceId] = static function (ContainerInterface $container) use ($adapterServiceId, $schemaId) {
        /** @var AdapterInterface $adapter */
        $adapter = $container->get($adapterServiceId);
        /** @var Schema $schema */
        $schema = $container->get($schemaId);

        return new Engine($adapter, $schema);
    };

    if ('default' === $name || (!isset($engines['default']) && !isset($diConfig[EngineInterface::class]))) {
        $diConfig[EngineInterface::class] = $engineServiceId;
        $diConfig[Schema::class] = $schemaId;
    }
}

$diConfig['cmsig_seal.engine_factory'] = static function (ContainerInterface $container) use ($engineServices) {
    $engines = [];
    foreach ($engineServices as $name => $engineServiceId) {
        /** @var \CmsIg\Seal\EngineInterface $engine */
        $engine = $container->get($engineServiceId);

        $engines[$name] = $engine;
    }

    return new EngineRegistry($engines);
};

$diConfig[EngineRegistry::class] = 'cmsig_seal.engine_factory';

return $diConfig;
