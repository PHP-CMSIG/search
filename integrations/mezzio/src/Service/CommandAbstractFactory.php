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

use CmsIg\Seal\EngineRegistry;
use CmsIg\Seal\Integration\Mezzio\Command\ReindexCommand;
use CmsIg\Seal\Reindex\ReindexProviderInterface;
use Psr\Container\ContainerInterface;

/**
 * @internal
 */
final class CommandAbstractFactory
{
    /**
     * @template T of object
     *
     * @param class-string<T> $className
     *
     * @return T
     */
    public function __invoke(ContainerInterface $container, string $className): object
    {
        $arguments = [$container->get(EngineRegistry::class)];

        if (ReindexCommand::class === $className) {
            /** @var array{cmsig_seal: array{reindex_providers: string[]}} $config */
            $config = $container->get('config');

            $reindexProviderNames = $config['cmsig_seal']['reindex_providers'];
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

            $arguments[] = $reindexProviders;
        }

        return new $className(...$arguments);
    }
}
