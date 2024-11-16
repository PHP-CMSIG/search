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

namespace CmsIg\Seal\Adapter\Elasticsearch\Tests;

use CmsIg\Seal\Adapter\Elasticsearch\ElasticsearchSchemaManager;
use CmsIg\Seal\Testing\AbstractSchemaManagerTestCase;
use CmsIg\Seal\Testing\TestingHelper;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Response\Elasticsearch;

class ElasticsearchSchemaManagerTest extends AbstractSchemaManagerTestCase
{
    private static Client $client;

    public static function setUpBeforeClass(): void
    {
        self::$client = ClientHelper::getClient();
        self::$schemaManager = new ElasticsearchSchemaManager(self::$client);

        parent::setUpBeforeClass();
    }

    public function testSimpleElasticsearchMapping(): void
    {
        $index = $this->schema->indexes[TestingHelper::INDEX_SIMPLE];
        $task = static::$schemaManager->createIndex($index, ['return_slow_promise_result' => true]);
        $task->wait();

        /** @var Elasticsearch $response */
        $response = self::$client->indices()->getMapping([
            'index' => $index->name,
        ]);

        $mapping = $response->asArray();

        $task = static::$schemaManager->dropIndex($index, ['return_slow_promise_result' => true]);
        $task->wait();

        $this->assertTrue(isset($mapping[$index->name]['mappings']['properties']));

        $this->assertSame([
            'id' => [
                'type' => 'keyword',
                'index' => false,
            ],
            'title' => [
                'type' => 'text',
            ],
        ], $mapping[$index->name]['mappings']['properties']);
    }

    public function testComplexElasticsearchMapping(): void
    {
        $index = $this->schema->indexes[TestingHelper::INDEX_COMPLEX];
        $task = static::$schemaManager->createIndex($index, ['return_slow_promise_result' => true]);
        $task->wait();

        /** @var Elasticsearch $response */
        $response = self::$client->indices()->getMapping([
            'index' => $index->name,
        ]);

        $mapping = $response->asArray();

        $task = static::$schemaManager->dropIndex($index, ['return_slow_promise_result' => true]);
        $task->wait();

        $this->assertTrue(isset($mapping[$index->name]['mappings']['properties']));

        $this->assertSame([
            'article' => [
                'type' => 'text',
            ],
            'blocks' => [
                'properties' => [
                    'embed' => [
                        'properties' => [
                            '_originalIndex' => [
                                'type' => 'integer',
                                'index' => false,
                            ],
                            'media' => [
                                'type' => 'text',
                                'index' => false,
                            ],
                            'title' => [
                                'type' => 'text',
                            ],
                        ],
                    ],
                    'text' => [
                        'properties' => [
                            '_originalIndex' => [
                                'type' => 'integer',
                                'index' => false,
                            ],
                            'description' => [
                                'type' => 'text',
                            ],
                            'media' => [
                                'type' => 'integer',
                                'index' => false,
                                'doc_values' => false,
                            ],
                            'title' => [
                                'type' => 'text',
                            ],
                        ],
                    ],
                ],
            ],
            'categoryIds' => [
                'type' => 'integer',
                'index' => false,
            ],
            'comments' => [
                'properties' => [
                    'email' => [
                        'type' => 'text',
                        'index' => false,
                    ],
                    'text' => [
                        'type' => 'text',
                    ],
                ],
            ],
            'commentsCount' => [
                'type' => 'integer',
                'index' => false,
            ],
            'created' => [
                'type' => 'date',
                'index' => false,
            ],
            'footer' => [
                'properties' => [
                    'title' => [
                        'type' => 'text',
                    ],
                ],
            ],
            'header' => [
                'properties' => [
                    'image' => [
                        'properties' => [
                            'media' => [
                                'type' => 'integer',
                                'index' => false,
                                'doc_values' => false,
                            ],
                        ],
                    ],
                    'video' => [
                        'properties' => [
                            'media' => [
                                'type' => 'text',
                                'index' => false,
                            ],
                        ],
                    ],
                ],
            ],
            'isSpecial' => [
                'type' => 'boolean',
                'index' => false,
            ],
            'location' => [
                'type' => 'geo_point',
                'index' => false,
            ],
            'rating' => [
                'type' => 'float',
                'index' => false,
            ],
            'tags' => [
                'type' => 'text',
                'fields' => [
                    'raw' => [
                        'type' => 'keyword',
                    ],
                ],
            ],
            'title' => [
                'type' => 'text',
            ],
            'uuid' => [
                'type' => 'keyword',
                'index' => false,
            ],
        ], $mapping[$index->name]['mappings']['properties']);
    }
}
