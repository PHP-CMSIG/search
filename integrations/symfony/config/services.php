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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use CmsIg\Seal\Integration\Symfony\Command\IndexCreateCommand;
use CmsIg\Seal\Integration\Symfony\Command\IndexDropCommand;
use CmsIg\Seal\Integration\Symfony\Command\ReindexCommand;
use CmsIg\Seal\Adapter\AdapterFactory;
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
use CmsIg\Seal\EngineRegistry;

/*
 * @internal
 */
return static function (ContainerConfigurator $container) {
    // -------------------------------------------------------------------//
    // Commands                                                           //
    // -------------------------------------------------------------------//
    $container->services()
        ->set('seal.index_create_command', IndexCreateCommand::class)
        ->args([
            service('seal.engine_registry'),
        ])
        ->tag('console.command');

    $container->services()
        ->set('seal.index_drop_command', IndexDropCommand::class)
        ->args([
            service('seal.engine_registry'),
        ])
        ->tag('console.command');

    $container->services()
        ->set('seal.reindex_command', ReindexCommand::class)
        ->args([
            service('seal.engine_registry'),
            tagged_iterator('seal.reindex_provider'),
        ])
        ->tag('console.command');

    // -------------------------------------------------------------------//
    // Services                                                           //
    // -------------------------------------------------------------------//
    $container->services()
        ->set('seal.engine_registry', EngineRegistry::class)
        ->args([
            tagged_iterator('seal.engine', 'name'),
        ])
        ->alias(EngineRegistry::class, 'seal.engine_registry');

    $container->services()
        ->set('seal.adapter_factory', AdapterFactory::class)
            ->args([
                tagged_iterator('seal.adapter_factory', null, 'getName'),
            ])
        ->alias(AdapterFactory::class, 'seal.adapter_factory');

    if (\class_exists(AlgoliaAdapterFactory::class)) {
        $container->services()
            ->set('seal.algolia.adapter_factory', AlgoliaAdapterFactory::class)
            ->args([
                service('service_container'),
            ])
            ->tag('seal.adapter_factory', ['name' => AlgoliaAdapterFactory::getName()]);
    }

    if (\class_exists(ElasticsearchAdapterFactory::class)) {
        $container->services()
            ->set('seal.elasticsearch.adapter_factory', ElasticsearchAdapterFactory::class)
            ->args([
                service('service_container'),
            ])
            ->tag('seal.adapter_factory', ['name' => ElasticsearchAdapterFactory::getName()]);
    }

    if (\class_exists(LoupeAdapterFactory::class)) {
        $container->services()
            ->set('seal.loupe.adapter_factory', LoupeAdapterFactory::class)
            ->args([
                service('service_container'),
            ])
            ->tag('seal.adapter_factory', ['name' => LoupeAdapterFactory::getName()]);
    }

    if (\class_exists(OpensearchAdapterFactory::class)) {
        $container->services()
            ->set('seal.opensearch.adapter_factory', OpensearchAdapterFactory::class)
            ->args([
                service('service_container'),
            ])
            ->tag('seal.adapter_factory', ['name' => OpensearchAdapterFactory::getName()]);
    }

    if (\class_exists(MeilisearchAdapterFactory::class)) {
        $container->services()
            ->set('seal.meilisearch.adapter_factory', MeilisearchAdapterFactory::class)
            ->args([
                service('service_container'),
            ])
            ->tag('seal.adapter_factory', ['name' => MeilisearchAdapterFactory::getName()]);
    }

    if (\class_exists(MemoryAdapterFactory::class)) {
        $container->services()
            ->set('seal.memory.adapter_factory', MemoryAdapterFactory::class)
            ->args([
                service('service_container'),
            ])
            ->tag('seal.adapter_factory', ['name' => MemoryAdapterFactory::getName()]);
    }

    if (\class_exists(RediSearchAdapterFactory::class)) {
        $container->services()
            ->set('seal.redis.adapter_factory', RediSearchAdapterFactory::class)
            ->args([
                service('service_container'),
            ])
            ->tag('seal.adapter_factory', ['name' => RediSearchAdapterFactory::getName()]);
    }

    if (\class_exists(SolrAdapterFactory::class)) {
        $container->services()
            ->set('seal.solr.adapter_factory', SolrAdapterFactory::class)
            ->args([
                service('service_container'),
            ])
            ->tag('seal.adapter_factory', ['name' => SolrAdapterFactory::getName()]);
    }

    if (\class_exists(TypesenseAdapterFactory::class)) {
        $container->services()
            ->set('seal.typesense.adapter_factory', TypesenseAdapterFactory::class)
            ->args([
                service('service_container'),
            ])
            ->tag('seal.adapter_factory', ['name' => TypesenseAdapterFactory::getName()]);
    }

    // ...

    if (\class_exists(ReadWriteAdapterFactory::class)) {
        $container->services()
            ->set('seal.read_write.adapter_factory', ReadWriteAdapterFactory::class)
            ->args([
                service('service_container'),
                'seal.adapter.',
            ])
            ->tag('seal.adapter_factory', ['name' => ReadWriteAdapterFactory::getName()]);
    }

    if (\class_exists(MultiAdapterFactory::class)) {
        $container->services()
            ->set('seal.multi.adapter_factory', MultiAdapterFactory::class)
            ->args([
                service('service_container'),
                'seal.adapter.',
            ])
            ->tag('seal.adapter_factory', ['name' => MultiAdapterFactory::getName()]);
    }
};
