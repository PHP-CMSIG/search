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

namespace CmsIg\Seal\Odm\Tests\Schema\Fixtures\InvalidDateSubclass;

use CmsIg\Seal\Odm\Attribute\Field;
use CmsIg\Seal\Odm\Attribute\Index;

class CustomDate extends \DateTimeImmutable
{
}

#[Index('invalid')]
class Document
{
    #[Field]
    public CustomDate $created;
}
