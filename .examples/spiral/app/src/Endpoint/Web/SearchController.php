<?php

declare(strict_types=1);

namespace App\Endpoint\Web;

use CmsIg\Seal\Adapter\AdapterInterface;
use CmsIg\Seal\EngineInterface;
use CmsIg\Seal\EngineRegistry;
use Spiral\Prototype\Traits\PrototypeTrait;
use Spiral\Router\Annotation\Route;

/**
 * Simple home page controller. It renders home page template and also provides
 * an example of exception page.
 */
final class SearchController
{
    /**
     * Read more about Prototyping:.
     *
     * @see https://spiral.dev/docs/basics-prototype/#installation
     */
    use PrototypeTrait;

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

    public function __construct(
        private readonly EngineRegistry $engineRegistry,
    ) {
        $this->algoliaEngine = $this->engineRegistry->getEngine('algolia');
        $this->meilisearchEngine = $this->engineRegistry->getEngine('meilisearch');
        $this->elasticsearchEngine = $this->engineRegistry->getEngine('elasticsearch');
        $this->loupeEngine = $this->engineRegistry->getEngine('loupe');
        $this->memoryEngine = $this->engineRegistry->getEngine('memory');
        $this->opensearchEngine = $this->engineRegistry->getEngine('opensearch');
        $this->solrEngine = $this->engineRegistry->getEngine('solr');
        $this->redisearchEngine = $this->engineRegistry->getEngine('redisearch');
        $this->typesenseEngine = $this->engineRegistry->getEngine('typesense');
        $this->multiEngine = $this->engineRegistry->getEngine('multi');
        $this->readWriteEngine = $this->engineRegistry->getEngine('read-write');
    }

    #[Route(route: '/', name: 'index')]
    public function index(): string
    {
        $engineNames = \implode(', ', \array_keys([...$this->engineRegistry->getEngines()]));

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
                        <strong>Registred Engines</strong>: $engineNames
                    </div>
                </body>
            </html>
            HTML;
    }

    #[Route(route: '/algolia', name: 'algolia')]
    public function algolia(): string
    {
        $class = $this->getAdapterClass($this->algoliaEngine);

        return <<<HTML
            <!doctype html>
            <html>
                <head>
                    <title>Algolia</title>
                </head>
                <body>
                    <h1>$class</h1>
                </body>
            </html>
            HTML;
    }

    #[Route(route: '/meilisearch', name: 'meilisearch')]
    public function meilisearch(): string
    {
        $class = $this->getAdapterClass($this->meilisearchEngine);

        return <<<HTML
            <!doctype html>
            <html>
                <head>
                    <title>Meilisearch</title>
                </head>
                <body>
                    <h1>$class</h1>
                </body>
            </html>
            HTML;
    }

    #[Route(route: '/elasticsearch', name: 'elasticsearch')]
    public function elasticsearch(): string
    {
        $class = $this->getAdapterClass($this->elasticsearchEngine);

        return <<<HTML
            <!doctype html>
            <html>
                <head>
                    <title>Elasticsearch</title>
                </head>
                <body>
                    <h1>$class</h1>
                </body>
            </html>
            HTML;
    }

    #[Route(route: '/loupe', name: 'loupe')]
    public function loupe(): string
    {
        $class = $this->getAdapterClass($this->loupeEngine);

        return <<<HTML
            <!doctype html>
            <html>
                <head>
                    <title>Loupe</title>
                </head>
                <body>
                    <h1>$class</h1>
                </body>
            </html>
            HTML;
    }

    #[Route(route: '/memory', name: 'memory')]
    public function memory(): string
    {
        $class = $this->getAdapterClass($this->memoryEngine);

        return <<<HTML
            <!doctype html>
            <html>
                <head>
                    <title>Memory</title>
                </head>
                <body>
                    <h1>$class</h1>
                </body>
            </html>
            HTML;
    }

    #[Route(route: '/opensearch', name: 'opensearch')]
    public function opensearch(): string
    {
        $class = $this->getAdapterClass($this->opensearchEngine);

        return <<<HTML
            <!doctype html>
            <html>
                <head>
                    <title>Opensearch</title>
                </head>
                <body>
                    <h1>$class</h1>
                </body>
            </html>
            HTML;
    }

    #[Route(route: '/solr', name: 'solr')]
    public function solr(): string
    {
        $class = $this->getAdapterClass($this->solrEngine);

        return <<<HTML
            <!doctype html>
            <html>
                <head>
                    <title>Solr</title>
                </head>
                <body>
                    <h1>$class</h1>
                </body>
            </html>
            HTML;
    }

    #[Route(route: '/redisearch', name: 'redisearch')]
    public function redisearch(): string
    {
        $class = $this->getAdapterClass($this->redisearchEngine);

        return <<<HTML
            <!doctype html>
            <html>
                <head>
                    <title>RediSearch</title>
                </head>
                <body>
                    <h1>$class</h1>
                </body>
            </html>
            HTML;
    }

    #[Route(route: '/typesense', name: 'typesense')]
    public function typesense(): string
    {
        $class = $this->getAdapterClass($this->typesenseEngine);

        return <<<HTML
            <!doctype html>
            <html>
                <head>
                    <title>Typesense</title>
                </head>
                <body>
                    <h1>$class</h1>
                </body>
            </html>
            HTML;
    }

    #[Route(route: '/multi', name: 'multi')]
    public function multi(): string
    {
        $class = $this->getAdapterClass($this->multiEngine);

        return <<<HTML
            <!doctype html>
            <html>
                <head>
                    <title>Multi</title>
                </head>
                <body>
                    <h1>$class</h1>
                </body>
            </html>
            HTML;
    }

    #[Route(route: '/read-write', name: 'read-write')]
    public function readWrite(): string
    {
        $class = $this->getAdapterClass($this->readWriteEngine);

        return <<<HTML
            <!doctype html>
            <html>
                <head>
                    <title>Read-Write</title>
                </head>
                <body>
                    <h1>$class</h1>
                </body>
            </html>
            HTML;
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

    /**
     * Example of exception page.
     */
    #[Route(route: '/exception', name: 'exception')]
    public function exception(): never
    {
        throw new \Exception('This is a test exception.');
    }
}
