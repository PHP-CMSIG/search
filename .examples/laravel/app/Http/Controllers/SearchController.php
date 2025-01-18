<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use CmsIg\Seal\Adapter\AdapterInterface;
use CmsIg\Seal\EngineInterface;
use CmsIg\Seal\Integration\Laravel\Facade\Engine as EngineFacade;
use CmsIg\Seal\Integration\Laravel\Facade\EngineRegistry as EngineRegistryFacade;
use Symfony\Component\HttpFoundation\Response;

class SearchController extends Controller
{
    private readonly EngineInterface $algoliaEngine;
    private readonly EngineInterface $meilisearchEngine;
    private readonly EngineInterface $elasticsearchEngine;
    private readonly EngineInterface $loupeEngine;
    private readonly EngineInterface $memoryEngine;
    private readonly EngineInterface $opensearchEngine;
    private readonly EngineInterface $solrEngine;
    private readonly EngineInterface $redisearchEngine;
    private readonly EngineInterface $typesenseEngine;
    private readonly EngineInterface $multiEngine;
    private readonly EngineInterface $readWriteEngine;

    public function __construct()
    {
        $this->algoliaEngine = EngineRegistryFacade::getEngine('algolia');
        $this->meilisearchEngine = EngineRegistryFacade::getEngine('meilisearch');
        $this->elasticsearchEngine = EngineRegistryFacade::getEngine('elasticsearch');
        $this->loupeEngine = EngineRegistryFacade::getEngine('loupe');
        $this->memoryEngine = EngineRegistryFacade::getEngine('memory');
        $this->opensearchEngine = EngineRegistryFacade::getEngine('opensearch');
        $this->solrEngine = EngineRegistryFacade::getEngine('solr');
        $this->redisearchEngine = EngineRegistryFacade::getEngine('redisearch');
        $this->typesenseEngine = EngineRegistryFacade::getEngine('typesense');
        $this->multiEngine = EngineRegistryFacade::getEngine('multi');
        $this->readWriteEngine = EngineRegistryFacade::getEngine('read-write');
    }

    public function home(): string
    {
        $engineNames = \implode(', ', \array_keys([...EngineRegistryFacade::getEngines()]));

        $engineFacadeClass = EngineFacade::class;
        $engineRegistryFacadeClass = EngineRegistryFacade::class;
        $engineFacadeTargetClass = EngineFacade::getFacadeRoot()::class; // @phpstan-ignore-line
        $engineRegistryFacadeTargetClass = EngineRegistryFacade::getFacadeRoot()::class; // @phpstan-ignore-line

        return
            <<<HTML
            <!doctype html>
            <html>
                <head>
                    <title>Search Engines</title>
                </head>
                <body>
                    <h1>Adapters</h1>
                    <ul>
                        <li><a href="/algolia">Algolia</a></li>
                        <li><a href="/elasticsearch">Elasticsearch</a></li>
                        <li><a href="/loupe">Loupe</a></li>
                        <li><a href="/meilisearch">Meilisearch</a></li>
                        <li><a href="/memory">Memory</a></li>
                        <li><a href="/opensearch">Opensearch</a></li>
                        <li><a href="/redisearch">RediSearch</a></li>
                        <li><a href="/solr">Solr</a></li>
                        <li><a href="/typesense">Typesense</a></li>
                        <li>....</li>
                        <li><a href="/multi">Multi</a></li>
                        <li><a href="/read-write">Read-Write</a></li>
                    </ul>

                    <div>
                        <strong>Registered Engines</strong>: $engineNames
                    </div>

                    <div>
                        <h2>Facade Engines</h2>

                        <ul>
                            <li><strong>$engineFacadeClass</strong>: $engineFacadeTargetClass</li>
                            <li><strong>$engineRegistryFacadeClass</strong>: $engineRegistryFacadeTargetClass</li>
                        </ul>
                    </div>
                </body>
            </html>
            HTML;
    }

    public function algolia(): Response
    {
        $class = $this->getAdapterClass($this->algoliaEngine);

        return new Response(
            <<<HTML
            <!doctype html>
            <html>
                <head>
                    <title>Algolia</title>
                </head>
                <body>
                    <h1>$class</h1>
                </body>
            </html>
HTML
        );
    }

    public function meilisearch(): Response
    {
        $class = $this->getAdapterClass($this->meilisearchEngine);

        return new Response(
            <<<HTML
            <!doctype html>
            <html>
                <head>
                    <title>Meilisearch</title>
                </head>
                <body>
                    <h1>$class</h1>
                </body>
            </html>
HTML
        );
    }

    public function elasticsearch(): Response
    {
        $class = $this->getAdapterClass($this->elasticsearchEngine);

        return new Response(
            <<<HTML
            <!doctype html>
            <html>
                <head>
                    <title>Elasticsearch</title>
                </head>
                <body>
                    <h1>$class</h1>
                </body>
            </html>
HTML
        );
    }

    public function loupe(): Response
    {
        $class = $this->getAdapterClass($this->loupeEngine);

        return new Response(
            <<<HTML
            <!doctype html>
            <html>
                <head>
                    <title>Loupe</title>
                </head>
                <body>
                    <h1>$class</h1>
                </body>
            </html>
HTML
        );
    }

    public function memory(): Response
    {
        $class = $this->getAdapterClass($this->memoryEngine);

        return new Response(
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

    public function opensearch(): Response
    {
        $class = $this->getAdapterClass($this->opensearchEngine);

        return new Response(
            <<<HTML
            <!doctype html>
            <html>
                <head>
                    <title>Opensearch</title>
                </head>
                <body>
                    <h1>$class</h1>
                </body>
            </html>
HTML
        );
    }

    public function solr(): Response
    {
        $class = $this->getAdapterClass($this->solrEngine);

        return new Response(
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

    public function redisearch(): Response
    {
        $class = $this->getAdapterClass($this->redisearchEngine);

        return new Response(
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

    public function typesense(): Response
    {
        $class = $this->getAdapterClass($this->typesenseEngine);

        return new Response(
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

    public function multi(): Response
    {
        $class = $this->getAdapterClass($this->multiEngine);

        return new Response(
            <<<HTML
            <!doctype html>
            <html>
                <head>
                    <title>Multi</title>
                </head>
                <body>
                    <h1>$class</h1>
                </body>
            </html>
HTML
        );
    }

    public function readWrite(): Response
    {
        $class = $this->getAdapterClass($this->readWriteEngine);

        return new Response(
            <<<HTML
            <!doctype html>
            <html>
                <head>
                    <title>Read-Write</title>
                </head>
                <body>
                    <h1>$class</h1>
                </body>
            </html>
HTML
        );
    }

    private function getAdapterClass(EngineInterface $engine): string
    {
        $reflection = new \ReflectionClass($engine);
        $propertyReflection = $reflection->getProperty('adapter');
        $propertyReflection->setAccessible(true);

        /** @var AdapterInterface $object */
        $object = $propertyReflection->getValue($engine);

        return $object::class;
    }
}
