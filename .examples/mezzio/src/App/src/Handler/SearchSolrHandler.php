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

class SearchSolrHandler implements RequestHandlerInterface
{
    private readonly EngineInterface $solrEngine;

    public function __construct(
        private readonly EngineRegistry $engineRegistry,
    ) {
        $this->solrEngine = $this->engineRegistry->getEngine('solr');
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $class = AdapterClassHelper::getAdapterClass($this->solrEngine);

        return new HtmlResponse(
            <<<HTML
            <!doctype html>
            <html>
                <head>
                    <title>Solr</title>
                </head>
                <body>
                    <h1>$class</h1>
                </body>
            </html>
HTML
        );
    }
}
