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

namespace CmsIg\Seal\Odm\Tests\Reindex;

use CmsIg\Seal\Odm\Mapper\OdmDataMapper;
use CmsIg\Seal\Odm\Reindex\OdmDataMapperReindexProvider;
use CmsIg\Seal\Odm\Reindex\OdmStaticReindexProviderInterface;
use CmsIg\Seal\Odm\Schema\Loader\AttributeLoader;
use CmsIg\Seal\Odm\Tests\Reindex\Fixtures\Schema\Blog;
use CmsIg\Seal\Reindex\ReindexConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OdmDataMapperReindexProvider::class)]
class OdmDataMapperReindexProviderTest extends TestCase
{
    private OdmDataMapperReindexProvider $provider;

    protected function setUp(): void
    {
        $attributeLoader = new AttributeLoader([__DIR__ . '/Fixtures/Schema']);
        $odmDataMapper = new OdmDataMapper($attributeLoader->load());

        $odmProviders = new class() implements OdmStaticReindexProviderInterface {
            public function total(): int
            {
                return 2;
            }

            public function provide(ReindexConfig $reindexConfig): \Generator
            {
                yield new Blog('1', 'Test');
                yield new Blog('2', 'Other');
            }

            public function getIndexName(): string
            {
                return 'blog';
            }
        };

        $this->provider = new OdmDataMapperReindexProvider([
            'blog' => $odmProviders,
        ], $odmDataMapper);
    }

    public function testTotal(): void
    {
        self::assertSame(
            2,
            $this->provider->total('blog'),
        );
    }

    public function testProvide(): void
    {
        $result = [
            ...$this->provider->provide('blog', ReindexConfig::create()->withIndex('blog')),
        ];

        self::assertCount(
            2,
            $result,
        );

        self::assertSame(
            [
                [
                    'id' => '1',
                    'text' => 'Test',
                ],
                [
                    'id' => '2',
                    'text' => 'Other',
                ],
            ],
            $result,
        );
    }
}
