<?php

declare(strict_types=1);

namespace AppTest\Handler;

use AppTest\FunctionalTestCase;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Symfony\Component\DomCrawler\Crawler;

final class SearchHandlerTest extends FunctionalTestCase
{
    private ServerRequestFactoryInterface $requestFactory;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var ServerRequestFactoryInterface $requestFactory */
        $requestFactory = $this->container->get(ServerRequestFactoryInterface::class);

        $this->requestFactory = $requestFactory;
    }

    public function testSearchHandlers(): void
    {
        $request = $this->requestFactory->createServerRequest('GET', '/');
        $response = $this->app->handle($request);

        self::assertSame(200, $response->getStatusCode());
        $content = $response->getBody()->__toString();
        self::assertStringContainsString('<title>Search Engines</title>', $content);

        $crawler = $this->crawler($content);
        $crawler->filter('a')->each(function ($node) {
            $href = $node->attr('href');
            $this->assertNotNull($href);
            $request = $this->requestFactory->createServerRequest('GET', $href);
            $response = $this->app->handle($request);
            self::assertSame(200, $response->getStatusCode());
            $content = $response->getBody()->__toString();

            $crawler = $this->crawler($content);

            $this->assertStringContainsString($node->text(), $crawler->filter('title')->first()->text());

            $h1s = $crawler->filter('h1');
            $this->assertCount(1, $h1s);

            $h1 = $h1s->first();

            $this->assertStringContainsString(\str_replace('-', '', (string) $node->text()), $h1->text());
        });
    }

    private function crawler(string $content): Crawler
    {
        return new Crawler($content);
    }
}
