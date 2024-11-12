<?php

declare(strict_types=1);

namespace App\Handler;

use App\Helper\AdapterClassHelper;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use CmsIg\Seal\EngineInterface;
use CmsIg\Seal\EngineRegistry;

class SearchRedisearchHandler implements RequestHandlerInterface
{
    private readonly EngineInterface $redisearchEngine;

    public function __construct(
        private readonly EngineRegistry $engineRegistry,
    ) {
        $this->redisearchEngine = $this->engineRegistry->getEngine('redisearch');
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $class = AdapterClassHelper::getAdapterClass($this->redisearchEngine);

        return new HtmlResponse(
            <<<HTML
            <!doctype html>
            <html>
                <head>
                    <title>RediSearch</title>
                </head>
                <body>
                    <h1>$class</h1>
                </body>
            </html>
HTML
        );
    }
}
