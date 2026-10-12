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

namespace CmsIg\Seal\QueryTranslator;

use CmsIg\Seal\Search\Condition\AbstractGroupCondition;
use CmsIg\Seal\Search\Condition\AndCondition;
use CmsIg\Seal\Search\Condition\EqualCondition;
use CmsIg\Seal\Search\Condition\GreaterThanCondition;
use CmsIg\Seal\Search\Condition\GreaterThanEqualCondition;
use CmsIg\Seal\Search\Condition\IdentifierCondition;
use CmsIg\Seal\Search\Condition\LessThanCondition;
use CmsIg\Seal\Search\Condition\LessThanEqualCondition;
use CmsIg\Seal\Search\Condition\NotEqualCondition;
use CmsIg\Seal\Search\Condition\OrCondition;
use CmsIg\Seal\Search\Condition\SearchCondition;
use QueryTranslator\Languages\Galach\Parser;
use QueryTranslator\Languages\Galach\TokenExtractor;
use QueryTranslator\Languages\Galach\Tokenizer;
use QueryTranslator\Languages\Galach\Values\Node\Group;
use QueryTranslator\Languages\Galach\Values\Node\LogicalAnd;
use QueryTranslator\Languages\Galach\Values\Node\LogicalOr;
use QueryTranslator\Languages\Galach\Values\Node\Mandatory;
use QueryTranslator\Languages\Galach\Values\Node\Prohibited;
use QueryTranslator\Languages\Galach\Values\Node\Term;
use QueryTranslator\Languages\Galach\Values\Token\Word;
use QueryTranslator\Values\Node;
use QueryTranslator\Values\SyntaxTree;

final class QueryTranslator
{
    /**
     * @return array<EqualCondition|GreaterThanCondition|GreaterThanEqualCondition|IdentifierCondition|LessThanCondition|LessThanEqualCondition|NotEqualCondition|AndCondition|OrCondition>
     */
    public static function generate(string $query): array
    {
        $tokenizer = new Tokenizer(new TokenExtractor\Full());
        $parser = new Parser();

        $tokenSequence = $tokenizer->tokenize($query);

        /** @var SyntaxTree $syntaxTree */
        $syntaxTree = $parser->parse($tokenSequence);

        return self::generateFromNodes(self::fetch($syntaxTree->rootNode->getNodes()));
    }

    /**
     * @param \Generator<array{
     *      current: Node,
     *      next: Node|null,
     *  }> $nodes
     *
     * @return array<EqualCondition|GreaterThanCondition|GreaterThanEqualCondition|IdentifierCondition|LessThanCondition|LessThanEqualCondition|NotEqualCondition|AndCondition|OrCondition>
     */
    private static function generateFromNodes(iterable $nodes): array
    {
        $filters = [];
        while ($current = $nodes->current()) {
            $currentNode = $current['current'];
            $nextNode = $current['next'];

            match (true) {
                $currentNode instanceof Term => $filters[] = (function (iterable $nodes, Term $currentNode, Node|null $nextNode) {
                    \assert($currentNode->token instanceof Word);

                    $search = $currentNode->token->word;

                    while ($nextNode instanceof Term) {
                        \assert($nextNode->token instanceof Word);

                        $search .= ' ' . $nextNode->token->word;

                        $nodes->next();
                        $nextNode = $nodes->current()['next'];
                    }

                    return new SearchCondition($search);
                })($nodes, $currentNode, $nextNode),
                $currentNode instanceof Mandatory => $filters[] = (function (iterable $nodes, Mandatory $currentNode, Node|null $nextNode) {
                    \assert($currentNode->operand instanceof Term);
                    \assert($currentNode->operand->token instanceof Word);

                    return new EqualCondition(
                        $currentNode->operand->token->domain,
                        $currentNode->operand->token->word,
                    );
                })($nodes, $currentNode, $nextNode),
                $currentNode instanceof Prohibited => $filters[] = (function (iterable $nodes, Prohibited $currentNode, Node|null $nextNode) {
                    \assert($currentNode->operand instanceof Term);
                    \assert($currentNode->operand->token instanceof Word);

                    return new NotEqualCondition(
                        $currentNode->operand->token->domain,
                        $currentNode->operand->token->word,
                    );
                })($nodes, $currentNode, $nextNode),
                $currentNode instanceof Group || $currentNode instanceof LogicalAnd => $filters[] = (function (iterable $nodes, Group|LogicalAnd $currentNode, Node|null $nextNode) {
                    $conditionFilters = self::generateFromNodes(self::fetch($currentNode->getNodes()));

                    if (1 === \count($conditionFilters) && $conditionFilters instanceof AbstractGroupCondition) {
                        return $conditionFilters[0];
                    }

                    return new AndCondition(...$conditionFilters);
                })($nodes, $currentNode, $nextNode),
                $currentNode instanceof LogicalOr => $filters[] = (function (iterable $nodes, LogicalOr $currentNode, Node|null $nextNode) {
                    $conditionFilters = self::generateFromNodes(self::fetch($currentNode->getNodes()));

                    return new OrCondition(...$conditionFilters);
                })($nodes, $currentNode, $nextNode),
                default => throw new \InvalidArgumentException('Unsupported node type: ' . $currentNode::class),
            };

            $nodes->next();
        }

        return $filters;
    }

    /**
     * @return \Generator<array{
     *     current: Node,
     *     next: Node|null,
     * }>
     */
    private static function fetch(iterable $nodes): \Generator
    {
        $prevNode = null;
        foreach ($nodes as $node) {
            if (null === $prevNode) {
                $prevNode = $node;

                continue;
            }

            yield [
                'current' => $prevNode,
                'next' => $node,
            ];

            $prevNode = $node;
        }

        yield [
            'current' => $prevNode,
            'next' => null,
        ];
    }
}
