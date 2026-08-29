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

#[Index('multiple')]
class Multiple
{
    #[Identifier]
    public string $uuid;

    /**
     * @var array<string>
     */
    #[Field]
    public array $texts;

    /**
     * @var array<int>
     */
    #[Field]
    public array $commentsCounts;

    /**
     * @var array<float>
     */
    #[Field]
    public array $ratings;

    /**
     * @var array<bool>
     */
    #[Field]
    public array $isSpecials;

    /**
     * @var array<\DateTimeImmutable>
     */
    #[Field]
    public array $createds;
}
