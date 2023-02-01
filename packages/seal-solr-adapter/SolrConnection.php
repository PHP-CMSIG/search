<?php

namespace Schranz\Search\SEAL\Adapter\Solr;

use Schranz\Search\SEAL\Marshaller\FlattenMarshaller;
use Schranz\Search\SEAL\Task\SyncTask;
use Solarium\Client;
use Schranz\Search\SEAL\Adapter\ConnectionInterface;
use Schranz\Search\SEAL\Schema\Index;
use Schranz\Search\SEAL\Search\Condition;
use Schranz\Search\SEAL\Search\Result;
use Schranz\Search\SEAL\Search\Search;
use Schranz\Search\SEAL\Task\TaskInterface;

final class SolrConnection implements ConnectionInterface
{
    private FlattenMarshaller $marshaller;

    public function __construct(
        private readonly Client $client,
    ) {
        $this->marshaller = new FlattenMarshaller();
    }

    public function save(Index $index, array $document, array $options = []): ?TaskInterface
    {
        $identifierField = $index->getIdentifierField();

        /** @var string|null $identifier */
        $identifier = ((string) $document[$identifierField->name]) ?? null;

        $marshalledDocument = $this->marshaller->marshall($index->fields, $document);
        $marshalledDocument['id'] = $identifier;

        $update = $this->client->createUpdate();
        $indexDocument = $update->createDocument($marshalledDocument);

        $update->addDocuments([$indexDocument]);
        $update->addCommit();

        $this->client->getEndpoint()
            ->setCollection($index->name);

        $this->client->update($update);

        if (true !== ($options['return_slow_promise_result'] ?? false)) {
            return null;
        }

        return new SyncTask(null);
    }

    public function delete(Index $index, string $identifier, array $options = []): ?TaskInterface
    {
        $update = $this->client->createUpdate();
        $update->addDeleteById($identifier);
        $update->addCommit();

        $this->client->getEndpoint()
            ->setCollection($index->name);

        $this->client->update($update);

        if (true !== ($options['return_slow_promise_result'] ?? false)) {
            return null;
        }

        return new SyncTask(null);
    }

    public function search(Search $search): Result
    {
        // optimized single document query
        if (
            count($search->indexes) === 1
            && count($search->filters) === 1
            && $search->filters[0] instanceof Condition\IdentifierCondition
            && $search->offset === 0
            && $search->limit === 1
        ) {
            $this->client->getEndpoint()
                ->setCollection($search->indexes[\array_key_first($search->indexes)]->name);

            $query = $this->client->createRealtimeGet();
            $query->addId($search->filters[0]->identifier);
            $result = $this->client->realtimeGet($query);

            if (!$result->getNumFound()) {
                return new Result(
                    $this->hitsToDocuments($search->indexes, []),
                    0
                );
            }

            return new Result(
                $this->hitsToDocuments($search->indexes, [$result->getDocument()]),
                1
            );
        }

        if (count($search->indexes) !== 1) {
            throw new \RuntimeException('Solr does not yet support search multiple indexes: https://github.com/schranz-search/schranz-search/issues/28');
        }

        $index = $search->indexes[\array_key_first($search->indexes)];
        $this->client->getEndpoint()
            ->setCollection($index->name);


        $query = $this->client->createSelect();
        $helper = $query->getHelper();

        $queryText = null;

        $filters = [];
        foreach ($search->filters as $filter) {
            match (true) {
                $filter instanceof Condition\SearchCondition => $queryText = $filter->query,
                $filter instanceof Condition\IdentifierCondition => $filters[] = $index->getIdentifierField()->name . ':"' . $filter->identifier . '"', // TODO escape?
                $filter instanceof Condition\EqualCondition => $filters[] = $filter->field . ':"' . $filter->value . '"', // TODO escape?
                $filter instanceof Condition\NotEqualCondition => $filters[] = '-' . $filter->field . ':"' . $filter->value . '"', // TODO escape?
                $filter instanceof Condition\GreaterThanCondition => $filters[] = $filter->field . ':{' . $filter->value . ' TO *}', // TODO escape?
                $filter instanceof Condition\GreaterThanEqualCondition => $filters[] = $filter->field . ':[' . $filter->value . ' TO *]', // TODO escape?
                $filter instanceof Condition\LessThanCondition => $filters[] = $filter->field . ':{* TO ' . $filter->value . '}', // TODO escape?
                $filter instanceof Condition\LessThanEqualCondition => $filters[] = $filter->field . ':[* TO ' . $filter->value . ']', // TODO escape?
                default => throw new \LogicException($filter::class . ' filter not implemented.'),
            };
        }

        if ($queryText !== null) {
            $query->setFields($index->searchableFields);
            $query->setQuery($helper->escapePhrase($queryText));
        }

        foreach ($filters as $key => $filter) {
            $query->createFilterQuery('filter_' . $key)->setQuery($filter);
        }

        if ($search->offset) {
            $query->setStart($search->offset);
        }

        if ($search->limit) {
            $query->setRows($search->limit);
        }

        foreach ($search->sortBys as $field => $direction) {
            $query->addSort($field, $direction);
        }

        $result = $this->client->select($query);

        return new Result(
            $this->hitsToDocuments($search->indexes, $result->getDocuments()),
            $result->getNumFound()
        );
    }

    /**
     * @param Index[] $indexes
     * @param iterable<\Solarium\QueryType\Select\Result\Document> $hits
     *
     * @return \Generator<array<string, mixed>>
     */
    private function hitsToDocuments(array $indexes, iterable $hits): \Generator
    {
        $index = $indexes[\array_key_first($indexes)];

        foreach ($hits as $hit) {
            $hit = $hit->getFields();

            unset($hit['_version_']);

            if ($index->getIdentifierField()->name !== 'id') {
                $id = $hit['id'];
                unset($hit['id']);

                $hit[$index->getIdentifierField()->name] = $id;
            }

            yield $this->marshaller->unmarshall($index->fields, $hit);
        }
    }
}
