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

namespace CmsIg\Seal\Adapter\Algolia\Tests;

use Algolia\AlgoliaSearch\Api\SearchClient;
use CmsIg\Seal\Adapter\Algolia\AlgoliaSchemaManager;
use CmsIg\Seal\Testing\AbstractSchemaManagerTestCase;
use CmsIg\Seal\Testing\TestingHelper;

class AlgoliaSchemaManagerTest extends AbstractSchemaManagerTestCase
{
    private static SearchClient $client;

    /**
     * @var array<string>
     */
    private static array $expectedSettingsKeys = [
        'searchableAttributes',
        'attributesForFaceting',
        'replicas',
        'attributeForDistinct',
    ];

    public static function setUpBeforeClass(): void
    {
        self::$client = ClientHelper::getClient();
        self::$schemaManager = new AlgoliaSchemaManager(self::$client);

        parent::setUpBeforeClass();
    }

    public function testAlgoliaSettingsSimple(): void
    {
        $index = $this->schema->indexes[TestingHelper::INDEX_SIMPLE];

        $task = static::$schemaManager->createIndex($index, ['return_slow_promise_result' => true]);
        $task->wait();

        $returnedSettings = self::$client->getSettings($index->name);

        $settings = [];
        foreach (self::$expectedSettingsKeys as $key) {
            $settings[$key] = $returnedSettings[$key] ?? null;
        }

        $this->assertSame([
            'searchableAttributes' => [
                'title',
            ],
            'attributesForFaceting' => [
                'id',
            ],
            'replicas' => [
                $index->name . '__id_asc',
                $index->name . '__id_desc',
            ],
            'attributeForDistinct' => null,
        ], $settings);

        $task = static::$schemaManager->dropIndex($index, ['return_slow_promise_result' => true]);
        $task->wait();
    }

    public function testAlgoliaSettingsComplex(): void
    {
        $index = $this->schema->indexes[TestingHelper::INDEX_COMPLEX];

        $task = static::$schemaManager->createIndex($index, ['return_slow_promise_result' => true]);
        $task->wait();

        $returnedSettings = self::$client->getSettings($index->name);

        $settings = [];
        foreach (self::$expectedSettingsKeys as $key) {
            $settings[$key] = $returnedSettings[$key] ?? null;
        }

        $this->assertSame([
            'searchableAttributes' => [
                'title',
                'article',
                'blocks.text.title',
                'blocks.text.description',
                'blocks.embed.title',
                'footer.title',
                'comments.text',
                'tags',
            ],
            'attributesForFaceting' => [
                'uuid',
                'created',
                'commentsCount',
                'rating',
                'isSpecial',
                'tags',
                'categoryIds',
                '_geoloc',
            ],
            'replicas' => [
                $index->name . '__uuid_asc',
                $index->name . '__uuid_desc',
                $index->name . '__title_asc',
                $index->name . '__title_desc',
                $index->name . '__created_asc',
                $index->name . '__created_desc',
                $index->name . '__commentsCount_asc',
                $index->name . '__commentsCount_desc',
                $index->name . '__rating_asc',
                $index->name . '__rating_desc',
                $index->name . '___geoloc_asc',
                $index->name . '___geoloc_desc',
            ],
            'attributeForDistinct' => 'commentsCount',
        ], $settings);

        $task = static::$schemaManager->dropIndex($index, ['return_slow_promise_result' => true]);
        $task->wait();
    }
}
