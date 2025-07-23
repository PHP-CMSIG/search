<?php

declare(strict_types=1);

namespace CmsIg\Seal\Integration\Mezzio\Service;

use Psr\Container\ContainerInterface;

final readonly class PhpFileLoaderProviderFactory
{
    public function __invoke(ContainerInterface $container): PhpFileLoaderProvider
    {
        return new PhpFileLoaderProvider();
    }
}