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
use CmsIg\Seal\Adapter\Multi\MultiAdapterFactory;
use CmsIg\Seal\Adapter\ReadWrite\ReadWriteAdapterFactory;
use CmsIg\Seal\Engine;
use CmsIg\Seal\EngineInterface;
use CmsIg\Seal\EngineRegistry;
use CmsIg\Seal\Integration\Mezzio\ConfigProvider;
use Doctrine\DBAL\Schema\Schema;
use Psr\Container\ContainerInterface;

/**
 * @internal
 *
 * @phpstan-import-type TCmsSigSealConfig from ConfigProvider
 */
final class SealContainerFactory
{
    public function __invoke(ContainerInterface $container): SealContainer
    {
        /** @var array{cmsig_seal: TCmsSigSealConfig} $config */
        $config = $container->get('config');

        $config = $config['cmsig_seal'];

        $adapterFactoriesConfig = $config['adapter_factories'];

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
            $adapter = $adapterFactory->createAdapter($adapterDsn);

            $loaderProvider = $container->get(LoaderProviderInterface::class);
            \assert($loaderProvider instanceof LoaderProviderInterface);
            $loader = $loaderProvider->getLoader($name, $config);
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
