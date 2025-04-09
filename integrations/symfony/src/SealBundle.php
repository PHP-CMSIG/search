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

namespace CmsIg\Seal\Integration\Symfony;

use CmsIg\Seal\Integration\Symfony\DependencyInjection\SealExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * @experimental
 */
final class SealBundle extends AbstractBundle
{
    protected string $extensionAlias = 'cmsig_seal';

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->registerExtension(new SealExtension());
    }
}
