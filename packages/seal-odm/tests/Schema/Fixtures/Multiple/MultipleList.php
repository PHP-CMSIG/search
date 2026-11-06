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

namespace CmsIg\Seal\Odm\Tests\Schema\Fixtures\Multiple;

use CmsIg\Seal\Odm\Attribute\Field;
use CmsIg\Seal\Odm\Attribute\Identifier;
use CmsIg\Seal\Odm\Attribute\Index;

#[Index('multipleList')]
class MultipleList
{
    #[Identifier]
    public string $uuid;

    /**
     * @var list<string>
     */
    #[Field]
    public array $texts;

    /**
     * @var list<int>
     */
    #[Field]
    public array $commentsCounts;

    /**
     * @var list<float>
     */
    #[Field]
    public array $ratings;

    /**
     * @var list<bool>
     */
    #[Field]
    public array $isSpecials;

    /**
     * @var list<\DateTimeImmutable>
     */
    #[Field]
    public array $createds;
}
