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

namespace CmsIg\Seal\Integration\Mezzio;

use CmsIg\Seal\Adapter\AdapterFactory;
use CmsIg\Seal\Adapter\AdapterFactoryInterface;
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
use CmsIg\Seal\EngineInterface;
use CmsIg\Seal\EngineRegistry;
use CmsIg\Seal\Integration\Mezzio\Service\CommandAbstractFactory;
use CmsIg\Seal\Integration\Mezzio\Service\SealContainer;
use CmsIg\Seal\Integration\Mezzio\Service\SealContainerFactory;
use CmsIg\Seal\Integration\Mezzio\Service\SealContainerServiceAbstractFactory;
use CmsIg\Seal\Schema\Schema;

final class ConfigProvider
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        return [
            'laminas-cli' => $this->getCliConfig(),
            'dependencies' => $this->getDependencies(),
            'cmsig_seal' => [
                'adapter_factories' => $this->getAdapterFactories(), // we are going over a config as there are no tagged services in mezzio
                'index_name_prefix' => '',
                'schemas' => [],
                'engines' => [],
                'reindex_providers' => [],
            ],
        ];
    }

    /**
     * @return array{
     *     commands: array<string, class-string>
     * }
     */
    public function getCliConfig(): array
    {
        return [
            'commands' => [
                'cmsig:seal:index-create' => Command\IndexCreateCommand::class,
                'cmsig:seal:index-drop' => Command\IndexDropCommand::class,
                'cmsig:seal:reindex' => Command\ReindexCommand::class,
            ],
        ];
    }

    /**
     * @return array{
     *     factories: array<class-string, class-string>
     * }
     */
    public function getDependencies(): array
    {
        /** @var array<class-string, class-string> $adapterFactories */
        $adapterFactories = [];
        foreach ($this->getAdapterFactories() as $adapterFactoryClass) {
            $adapterFactories[$adapterFactoryClass] = SealContainerServiceAbstractFactory::class;
        }

        return [
            'factories' => [
                EngineRegistry::class => SealContainerServiceAbstractFactory::class,
                EngineInterface::class => SealContainerServiceAbstractFactory::class,
                Schema::class => SealContainerServiceAbstractFactory::class,
                AdapterFactory::class => SealContainerServiceAbstractFactory::class,
                SealContainer::class => SealContainerFactory::class,
                Command\IndexCreateCommand::class => CommandAbstractFactory::class,
                Command\IndexDropCommand::class => CommandAbstractFactory::class,
                Command\ReindexCommand::class => CommandAbstractFactory::class,
                ...$adapterFactories,
            ],
        ];
    }

    /**
     * @return array<string, class-string<AdapterFactoryInterface>>
     */
    private function getAdapterFactories(): array
    {
        $adapterFactories = [];

        if (\class_exists(AlgoliaAdapterFactory::class)) {
            $adapterFactories[AlgoliaAdapterFactory::getName()] = AlgoliaAdapterFactory::class;
        }

        if (\class_exists(ElasticsearchAdapterFactory::class)) {
            $adapterFactories[ElasticsearchAdapterFactory::getName()] = ElasticsearchAdapterFactory::class;
        }

        if (\class_exists(LoupeAdapterFactory::class)) {
            $adapterFactories[LoupeAdapterFactory::getName()] = LoupeAdapterFactory::class;
        }

        if (\class_exists(MeilisearchAdapterFactory::class)) {
            $adapterFactories[MeilisearchAdapterFactory::getName()] = MeilisearchAdapterFactory::class;
        }

        if (\class_exists(OpensearchAdapterFactory::class)) {
            $adapterFactories[OpensearchAdapterFactory::getName()] = OpensearchAdapterFactory::class;
        }

        if (\class_exists(MemoryAdapterFactory::class)) {
            $adapterFactories[MemoryAdapterFactory::getName()] = MemoryAdapterFactory::class;
        }

        if (\class_exists(RediSearchAdapterFactory::class)) {
            $adapterFactories[RediSearchAdapterFactory::getName()] = RediSearchAdapterFactory::class;
        }

        if (\class_exists(SolrAdapterFactory::class)) {
            $adapterFactories[SolrAdapterFactory::getName()] = SolrAdapterFactory::class;
        }

        if (\class_exists(TypesenseAdapterFactory::class)) {
            $adapterFactories[TypesenseAdapterFactory::getName()] = TypesenseAdapterFactory::class;
        }

        if (\class_exists(ReadWriteAdapterFactory::class)) {
            $adapterFactories[ReadWriteAdapterFactory::getName()] = ReadWriteAdapterFactory::class;
        }

        if (\class_exists(MultiAdapterFactory::class)) {
            $adapterFactories[MultiAdapterFactory::getName()] = MultiAdapterFactory::class;
        }

        return $adapterFactories;
    }
}
