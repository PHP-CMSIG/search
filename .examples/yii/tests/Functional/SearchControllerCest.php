<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\Support\FunctionalTester;
use HttpSoft\Message\ServerRequest;

use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertSame;
use function PHPUnit\Framework\assertStringContainsString;

use Symfony\Component\DomCrawler\Crawler;

final class SearchControllerCest
{
    public function testSearch(FunctionalTester $tester): void
    {
        $response = $tester->sendRequest(
            new ServerRequest(uri: '/'),
        );

        $content = $response->getBody()->getContents();
        assertSame(200, $response->getStatusCode(), $content);
        assertStringContainsString('<title>Search Engines</title>', $content);

        $crawler = $this->crawler($content);
        $crawler->filter('a')->each(function ($node) use ($tester) {
            $response = $tester->sendRequest(
                new ServerRequest(uri: (string) $node->attr('href')),
            );
            $content = $response->getBody()->getContents();
            assertSame(200, $response->getStatusCode(), $content);

            $crawler = $this->crawler($content);

            assertStringContainsString($node->text(), $crawler->filter('title')->first()->text());

            $h1s = $crawler->filter('h1');
            assertCount(1, $h1s);

            $h1 = $h1s->first();

            assertStringContainsString(\str_replace('-', '', (string) $node->text()), $h1->text());
        });
    }

    private function crawler(string $content): Crawler
    {
        return new Crawler($content);
    }
}
