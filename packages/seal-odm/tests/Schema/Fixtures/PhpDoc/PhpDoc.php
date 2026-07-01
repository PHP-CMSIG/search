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

namespace CmsIg\Seal\Odm\Tests\Schema\Fixtures\PhpDoc;

use CmsIg\Seal\Odm\Attribute\Field;
use CmsIg\Seal\Odm\Attribute\Identifier;
use CmsIg\Seal\Odm\Attribute\Index;

#[Index('phpdoc')]
class PhpDoc
{
    /**
     * @var int
     */
    #[Identifier]
    public $id;

    /**
     * @var string
     */
    #[Field]
    public $text;

    /**
     * @var int
     */
    #[Field]
    public $commentsCount;

    /**
     * @var float
     */
    #[Field]
    public $rating;

    /**
     * @var bool
     */
    #[Field]
    public $isSpecial;

    /**
     * @var \DateTimeImmutable
     */
    #[Field]
    public $created;
}
