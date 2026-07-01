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

namespace CmsIg\Seal\Odm\Tests\Mapper\Fixtures\Schema;

use CmsIg\Seal\Odm\Attribute\Field;

class NestedSubObject
{
    public function __construct(
        #[Field]
        public string $text,
        #[Field]
        public int $commentsCount,
        #[Field]
        public float $rating,
        #[Field]
        public bool $isSpecial,
        #[Field]
        public \DateTimeImmutable $created,
    ) {
    }
}
