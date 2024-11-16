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

class LessThanCondition
{
    public function __construct(
        public readonly string $field,
        public readonly string|int|float|bool $value,
    ) {
    }
}
