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

class SearchMemoryHandler implements RequestHandlerInterface
{
    private readonly EngineInterface $memoryEngine;

    public function __construct(
        private readonly EngineRegistry $engineRegistry,
    ) {
        $this->memoryEngine = $this->engineRegistry->getEngine('memory');
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $class = AdapterClassHelper::getAdapterClass($this->memoryEngine);

        return new HtmlResponse(
            <<<HTML
            <!doctype html>
            <html>
                <head>
                    <title>Memory</title>
                </head>
                <body>
                    <h1>$class</h1>
                </body>
            </html>
HTML
        );
    }
}
