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

namespace CmsIg\Seal\Odm\Tests\Schema\Fixtures\Dates;

use CmsIg\Seal\Odm\Attribute\Field;
use CmsIg\Seal\Odm\Attribute\Identifier;
use CmsIg\Seal\Odm\Attribute\Index;

#[Index('dates')]
class Document
{
    #[Identifier]
    public string $id = '1';

    #[Field(options: ['custom' => true])]
    public \DateTime $mutable;

    #[Field]
    public \DateTimeImmutable $immutable;

    #[Field]
    public \DateTimeInterface $interface;

    #[Field]
    public \DateTime|null $nullable = null;

    /** @var array<string, \DateTime> */
    #[Field]
    public array $dates = [];
}
