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

/**
 * @internal this class is internal please use AndCondition or OrCondition directly
 */
abstract class AbstractGroupCondition
{
    /**
     * @var array<EqualCondition|GreaterThanCondition|GreaterThanEqualCondition|IdentifierCondition|LessThanCondition|LessThanEqualCondition|NotEqualCondition|AndCondition|OrCondition>
     */
    public readonly array $conditions;

    /**
     * @param EqualCondition|GreaterThanCondition|GreaterThanEqualCondition|IdentifierCondition|LessThanCondition|LessThanEqualCondition|NotEqualCondition|AndCondition|OrCondition $conditions
     */
    public function __construct(...$conditions)
    {
        $this->conditions = $conditions;
    }
}
