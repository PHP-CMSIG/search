<?php

declare(strict_types=1);

namespace App\Handler;

use App\Helper\AdapterClassHelper;
use CmsIg\Seal\EngineInterface;
use CmsIg\Seal\EngineRegistry;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SearchTypesenseHandler implements RequestHandlerInterface
{
    private readonly EngineInterface $typesenseEngine;

    public function __construct(
        private readonly EngineRegistry $engineRegistry,
    ) {
        $this->typesenseEngine = $this->engineRegistry->getEngine('typesense');
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $class = AdapterClassHelper::getAdapterClass($this->typesenseEngine);

        return new HtmlResponse(
            <<<HTML
            <!doctype html>
            <html>
                <head>
                    <title>Typesense</title>
                </head>
                <body>
                    <h1>$class</h1>
                </body>
            </html>
HTML
        );
    }
}
