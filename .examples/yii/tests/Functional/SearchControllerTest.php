<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DomCrawler\Crawler;
use Yiisoft\Yii\Testing\FunctionalTester;

final class SearchControllerTest extends TestCase
{
    private FunctionalTester $tester;

    protected function setUp(): void
    {
        $this->tester = new FunctionalTester();
    }

    public function testSearch(): void
    {
        $this->tester->bootstrapApplication(\dirname(__DIR__, 2));
        $response = $this->tester->doRequest('GET', '/');

        $content = $response->getContent();
        self::assertSame(200, $response->getStatusCode(), $content);
        self::assertStringContainsString('<title>Search Engines</title>', $content);

        $crawler = $this->crawler($content);
        $crawler->filter('a')->each(function ($node) {
            $response = $this->tester->doRequest('GET', (string) $node->attr('href'));
            $content = $response->getContent();
            self::assertSame(200, $response->getStatusCode(), $content);

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
