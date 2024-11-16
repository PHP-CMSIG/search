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

namespace CmsIg\Seal\Integration\Mezzio\Service;

use CmsIg\Seal\Adapter\AdapterFactory;
use CmsIg\Seal\Adapter\AdapterFactoryInterface;
use CmsIg\Seal\Adapter\Multi\MultiAdapterFactory;
use CmsIg\Seal\Adapter\ReadWrite\ReadWriteAdapterFactory;
use CmsIg\Seal\Engine;
use CmsIg\Seal\EngineInterface;
use CmsIg\Seal\EngineRegistry;
use CmsIg\Seal\Schema\Loader\PhpFileLoader;
use Doctrine\DBAL\Schema\Schema;
use Psr\Container\ContainerInterface;

/**
 * @internal
 */
final class SealContainerFactory
{
    public function __invoke(ContainerInterface $container): SealContainer
    {
        /** @var array{cmsig_seal: mixed[]} $config */
        $config = $container->get('config');

        /**
         * @var array{
         *     index_name_prefix: string,
         *     schemas: array<string, array{
         *         dir: string,
         *         engine?: string,
         *     }>,
         *     engines: array<string, array{
         *         adapter: string,
         *     }>,
         *     adapter_factories: array<class-string, class-string<AdapterFactoryInterface>>,
         *     reindex_providers: string[],
         * } $config
         */
        $config = $config['cmsig_seal'];

        $indexNamePrefix = $config['index_name_prefix'];
        $adapterFactoriesConfig = $config['adapter_factories'];

        $engineSchemaDirs = [];
        foreach ($config['schemas'] as $options) {
            $engineSchemaDirs[$options['engine'] ?? 'default'][] = $options['dir'];
        }

        $sealContainer = new SealContainer($container);

        $adapterFactories = [];
        foreach ($adapterFactoriesConfig as $name => $adapterFactoryClass) {
            if (
                ReadWriteAdapterFactory::class === $adapterFactoryClass
                || MultiAdapterFactory::class === $adapterFactoryClass
            ) {
                $adapterFactories[$name] = new $adapterFactoryClass(
                    $sealContainer,
                    'cmsig_seal.adapter.',
                );

                continue;
            }

            $adapterFactories[$name] = new $adapterFactoryClass($sealContainer);
        }

        $adapterFactory = new AdapterFactory($adapterFactories);

        $sealContainer->set(AdapterFactory::class, $adapterFactory);

        $engineServices = [];
        foreach ($config['engines'] as $name => $engineConfig) {
            $adapterServiceId = 'cmsig_seal.adapter.' . $name;
            $engineServiceId = 'cmsig_seal.engine.' . $name;
            $schemaLoaderServiceId = 'cmsig_seal.schema_loader.' . $name;
            $schemaId = 'cmsig_seal.schema.' . $name;

            /** @var string $adapterDsn */
            $adapterDsn = $engineConfig['adapter'];
            $dirs = $engineSchemaDirs[$name] ?? [];

            $adapter = $adapterFactory->createAdapter($adapterDsn);
            $loader = new PhpFileLoader($dirs, $indexNamePrefix);
            $schema = $loader->load();

            $engine = new Engine($adapter, $schema);

            $engineServices[$name] = $engine;
            if ('default' === $name || (!isset($engineServices['default']) && !isset($config['engines']['default']))) {
                $engineServices['default'] = $engine;
                $sealContainer->set(EngineInterface::class, $engine);
                $sealContainer->set(Schema::class, $schema);
            }

            $sealContainer->set($adapterServiceId, $adapter);
            $sealContainer->set($engineServiceId, $engine);
            $sealContainer->set($schemaLoaderServiceId, $loader);
            $sealContainer->set($schemaId, $schema);
        }

        $sealContainer->set(EngineRegistry::class, new EngineRegistry($engineServices));

        return $sealContainer;
    }
}
