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

namespace CmsIg\Seal\Odm\Tests\Schema\Fixtures\Flags;

use CmsIg\Seal\Odm\Attribute\Field;
use CmsIg\Seal\Odm\Attribute\Identifier;
use CmsIg\Seal\Odm\Attribute\Index;

#[Index('flags')]
class Flags
{
    #[Identifier()]
    public string $uuid;

    #[Field(searchable: false, filterable: true, sortable: true, distinct: true, facet: true, options: ['customKey' => 'customValue'])]
    public string $text;

    #[Field(filterable: true, sortable: true, distinct: true, facet: true, options: ['customKey' => 'customValue'])]
    public int $commentsCount;

    #[Field(filterable: true, sortable: true, distinct: true, facet: true, options: ['customKey' => 'customValue'])]
    public float $rating;

    #[Field(filterable: true, sortable: true, distinct: true, facet: true, options: ['customKey' => 'customValue'])]
    public bool $isSpecial;

    #[Field(filterable: true, sortable: true, distinct: true, facet: true, options: ['customKey' => 'customValue'])]
    public \DateTimeImmutable $created;
}
