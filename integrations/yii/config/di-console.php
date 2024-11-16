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

use CmsIg\Seal\EngineRegistry;
use CmsIg\Seal\Integration\Yii\Command\IndexCreateCommand;
use CmsIg\Seal\Integration\Yii\Command\IndexDropCommand;
use CmsIg\Seal\Integration\Yii\Command\ReindexCommand;
use CmsIg\Seal\Reindex\ReindexProviderInterface;
use Psr\Container\ContainerInterface;

/** @var \Yiisoft\Config\Config $config */
/** @var array{"cmsig/seal-yii-module": array{reindex_providers: string[]}} $params */
$reindexProviderNames = $params['cmsig/seal-yii-module']['reindex_providers'];

$diConfig[IndexCreateCommand::class] = static function (ContainerInterface $container) {
    /** @var EngineRegistry $engineRegistry */
    $engineRegistry = $container->get(EngineRegistry::class);

    return new IndexCreateCommand($engineRegistry);
};

$diConfig[IndexDropCommand::class] = static function (ContainerInterface $container) {
    /** @var EngineRegistry $engineRegistry */
    $engineRegistry = $container->get(EngineRegistry::class);

    return new IndexDropCommand($engineRegistry);
};

$diConfig[ReindexCommand::class] = static function (ContainerInterface $container) use ($reindexProviderNames) {
    /** @var EngineRegistry $engineRegistry */
    $engineRegistry = $container->get(EngineRegistry::class);

    /** @var array<ReindexProviderInterface> $reindexProviders */
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

    return new ReindexCommand($engineRegistry, $reindexProviders);
};

return $diConfig;
