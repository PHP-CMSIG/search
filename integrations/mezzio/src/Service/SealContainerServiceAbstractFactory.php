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

use Psr\Container\ContainerInterface;

/**
 * @internal
 */
final class SealContainerServiceAbstractFactory
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
        /** @var SealContainer $sealContainer */
        $sealContainer = $container->get(SealContainer::class);

        return $sealContainer->get($className);
    }
}
