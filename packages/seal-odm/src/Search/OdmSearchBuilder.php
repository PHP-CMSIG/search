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

namespace CmsIg\Seal\Odm\Search;

use CmsIg\Seal\Adapter\SearcherInterface;
use CmsIg\Seal\Odm\Mapper\OdmDataMapperInterface;
use CmsIg\Seal\Search\Facet\AbstractFacet;
use CmsIg\Seal\Search\Search;
use CmsIg\Seal\Search\SearchBuilder;

/**
 * @template T of object
 */
final class OdmSearchBuilder
{
    /**
     * @param class-string<T> $documentClass
     */
    public function __construct(
        private readonly SearchBuilder $searchBuilder,
        private readonly OdmDataMapperInterface $dataMapper,
        private readonly string $documentClass,
    ) {
    }

    /**
     * @template U of object
     *
     * @param class-string<U> $documentClass
     *
     * @return OdmSearchBuilder<U>
     */
    public function index(string $documentClass): self
    {
        $searchBuilder = clone $this->searchBuilder;
        $searchBuilder->index($this->dataMapper->getIndexName($documentClass));

        return new self($searchBuilder, $this->dataMapper, $documentClass);
    }

    public function addFilter(object $filter): static
    {
        $this->searchBuilder->addFilter($filter);

        return $this;
    }

    /**
     * @param 'asc'|'desc' $direction
     */
    public function addSortBy(string $field, string $direction): static
    {
        $this->searchBuilder->addSortBy($field, $direction);

        return $this;
    }

    public function limit(int $limit): static
    {
        $this->searchBuilder->limit($limit);

        return $this;
    }

    public function offset(int $offset): static
    {
        $this->searchBuilder->offset($offset);

        return $this;
    }

    public function distinct(string|null $field): static
    {
        $this->searchBuilder->distinct($field);

        return $this;
    }

    /**
     * @param array<string> $fields
     */
    public function highlight(array $fields, string $preTag = '<mark>', string $postTag = '</mark>'): static
    {
        $this->searchBuilder->highlight($fields, $preTag, $postTag);

        return $this;
    }

    public function addFacet(AbstractFacet $facet): static
    {
        $this->searchBuilder->addFacet($facet);

        return $this;
    }

    public function getSearcher(): SearcherInterface
    {
        return $this->searchBuilder->getSearcher();
    }

    public function getSearch(): Search
    {
        return $this->searchBuilder->getSearch();
    }

    /**
     * @return OdmResult<T>
     */
    public function getResult(): OdmResult
    {
        $search = $this->searchBuilder->getSearch();
        $index = $search->index->name;
        $result = $this->searchBuilder->getSearcher()->search($search);

        return new OdmResult(
            (function (iterable $documents) use ($index): \Generator {
                foreach ($documents as $document) {
                    $object = $this->dataMapper->arrayToObject($index, $document);
                    if (!$object instanceof $this->documentClass) {
                        throw new \RuntimeException(\sprintf('Expected document of class "%s", got "%s".', $this->documentClass, $object::class));
                    }

                    yield $object;
                }
            })($result),
            $result->total(),
            $result->facets(),
        );
    }
}
