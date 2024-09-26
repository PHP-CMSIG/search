<?php

declare(strict_types=1);

/*
 * This file is part of the Schranz Search package.
 *
 * (c) Alexander Schranz <alexander@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Schranz\Search\SEAL\Search;

use Schranz\Search\SEAL\Adapter\SearcherInterface;
use Schranz\Search\SEAL\Schema\Index;
use Schranz\Search\SEAL\Schema\Schema;

final class SearchBuilder
{
    /**
     * @var array<string, Index>
     */
    private array $indexes = [];

    /**
     * @var object[]
     */
    private array $filters = [];

    /**
     * @var array<string, 'asc'|'desc'>
     */
    private array $sortBys = [];

    private int $offset = 0;

    private int|null $limit = null;

    /**
     * @var array<string>
     */
    private array $highlightFields = [];

    private string $highlightPreTag = '<mark>';

    private string $highlightPostTag = '</mark>';

    public function __construct(
        readonly private Schema $schema,
        readonly private SearcherInterface $searcher,
    ) {
    }

    public function addIndex(string $name): static
    {
        $this->indexes[$name] = $this->schema->indexes[$name];

        return $this;
    }

    public function addFilter(object $filter): static
    {
        $this->filters[] = $filter;

        return $this;
    }

    /**
     * @param 'asc'|'desc' $direction
     */
    public function addSortBy(string $field, string $direction): static
    {
        $this->sortBys[$field] = $direction;

        return $this;
    }

    public function limit(int $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    public function offset(int $offset): static
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * @param array<string> $fields
     */
    public function highlight(array $fields, string $preTag = '<mark>', string $postTag = '</mark>'): static
    {
        $this->highlightFields = $fields;
        $this->highlightPreTag = $preTag;
        $this->highlightPostTag = $postTag;

        return $this;
    }

    public function getSearcher(): SearcherInterface
    {
        return $this->searcher;
    }

    public function getSearch(): Search
    {
        return new Search(
            $this->indexes,
            $this->filters,
            $this->sortBys,
            $this->limit,
            $this->offset,
            $this->highlightFields,
            $this->highlightPreTag,
            $this->highlightPostTag,
        );
    }

    public function getResult(): Result
    {
        return $this->searcher->search($this->getSearch());
    }
}
