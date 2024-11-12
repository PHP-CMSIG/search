<?php

declare(strict_types=1);

namespace App\Handler;

use CmsIg\Seal\EngineRegistry;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SearchElasticsearchHandlerFactory
{
    public function __invoke(ContainerInterface $container): RequestHandlerInterface
    {
        $engineRegistry = $container->get(EngineRegistry::class);
        \assert($engineRegistry instanceof EngineRegistry);

        return new SearchElasticsearchHandler($engineRegistry);
    }
}
