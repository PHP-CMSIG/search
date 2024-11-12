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

namespace CmsIg\Seal\Search\Condition;

class InCondition
{
    /**
     * @param list<string|int|float|bool> $values
     */
    public function __construct(
        public readonly string $field,
        public readonly array $values,
    ) {
    }

    /**
     * @internal This method is for internal use and should not be called from outside.
     *
     * Some search engines do not support the `IN` operator, so we need to convert it to an `OR` condition.
     */
    public function createOrCondition(): OrCondition
    {
        /** @var array<EqualCondition|GreaterThanCondition|GreaterThanEqualCondition|IdentifierCondition|LessThanCondition|LessThanEqualCondition|NotEqualCondition|AndCondition|OrCondition> $conditions */
        $conditions = [];
        foreach ($this->values as $value) {
            $conditions[] = new EqualCondition($this->field, $value);
        }

        return new OrCondition(...$conditions);
    }
}
