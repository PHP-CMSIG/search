<?php

declare(strict_types=1);

/*
 * This file is part of the CMS-IG SEAL project.
 *
 * (c) Alexander Schranz <alexander@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CmsIg\Seal\Tests\Search\SearchBuilderFactory;

use CmsIg\Seal\Search\SearchBuilderFactory\SearchBuilderFactoryConfig;
use PHPUnit\Framework\TestCase;

class SearchBuilderFactoryConfigTest extends TestCase
{
    public function testFromArrayAndToArrayRoundTrip(): void
    {
        $data = [
            'filterFields' => ['tags', 'rating'],
            'sortFields' => ['rating'],
            'facetFields' => ['tags'],
            'distinctFields' => ['commentsCount'],
            'highlightFields' => ['title', 'article'],
            'allowSearch' => true,
            'allowIdentifier' => true,
            'maxLimit' => 50,
        ];

        $config = SearchBuilderFactoryConfig::fromArray($data);

        $this->assertSame($data, $config->toArray());
    }

    public function testFromArrayUsesDefaults(): void
    {
        $config = SearchBuilderFactoryConfig::fromArray([]);

        $this->assertSame((new SearchBuilderFactoryConfig())->toArray(), $config->toArray());
    }

    public function testFromArrayRejectsInvalidAllowSearchType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Value for "allowSearch" must be a boolean.');

        SearchBuilderFactoryConfig::fromArray([
            'allowSearch' => 1,
        ]);
    }
}
