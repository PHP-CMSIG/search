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

namespace CmsIg\Seal\QueryTranslator\Tests;

use CmsIg\Seal\QueryTranslator\QueryTranslator;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../seal-memory-adapter/src/MemorySearcher.php';

#[Covers(QueryTranslator::class)]
class QueryTranslatorTest extends TestCase
{
    public function testTranslateBasic(): void
    {
        $query = 'Hello World';

        $filters = QueryTranslator::generate($query);

        $this->assertCount(1, $filters);
    }

    public function testTranslateBasicWithEqualCondition(): void
    {
        $query = 'Hello World +tags:ABC';

        $filters = QueryTranslator::generate($query);

        $this->assertCount(2, $filters);
    }

    public function testTranslateBasicWithNotCondition(): void
    {
        $query = 'Hello World -tags:ABC';

        $filters = QueryTranslator::generate($query);

        $this->assertCount(2, $filters);
    }

    public function testTranslateBasicWithOrCondition(): void
    {
        $query = 'Hello World (+tags:ABC || +tags:DCF)';

        $filters = QueryTranslator::generate($query);

        $this->assertCount(2, $filters);
    }

    public function testTranslateBasicWithAndCondition(): void
    {
        $query = 'Hello World (+tags:ABC && +tags:DCF)';

        $filters = QueryTranslator::generate($query);

        $this->assertCount(2, $filters);
    }

    public function testTranslateBasicWithGreaterThanEqualCondition(): void
    {
        $query = 'Hello World +rating>=1.5';

        $filters = QueryTranslator::generate($query);

        $this->assertCount(2, $filters);
    }

    public function testTranslateBasicWithGreaterThanCondition(): void
    {
        $query = 'Hello World +rating>1.5';

        $filters = QueryTranslator::generate($query);

        $this->assertCount(2, $filters);
    }
}
