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

namespace CmsIg\Seal\Odm\Tests\Schema\Fixtures\Regression;

use CmsIg\Seal\Odm\Attribute\Field;
use CmsIg\Seal\Odm\Attribute\Identifier;
use CmsIg\Seal\Odm\Attribute\Index;

#[Index('regression')]
class Document
{
    #[Identifier]
    public string $id;

    #[Field(searchable: true)]
    public string $title;

    /** @var array<int, string> Tags with a description. */
    #[Field]
    public array $tags = [];
}
