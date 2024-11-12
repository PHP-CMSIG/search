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

namespace CmsIg\Seal\Search;

use CmsIg\Seal\Adapter\SearcherInterface;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Schema\Schema;

final class SearchBuilder
{
    private Index $index;

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

    public function __construct(
        readonly private Schema $schema,
        readonly private SearcherInterface $searcher,
    ) {
    }

    public function index(string $name): static
    {
        $this->index = $this->schema->indexes[$name];

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

    public function getSearch(): Search
    {
        return new Search(
            $this->index,
            $this->filters,
            $this->sortBys,
            $this->limit,
            $this->offset,
        );
    }

    public function getResult(): Result
    {
        return $this->searcher->search($this->getSearch());
    }
}
