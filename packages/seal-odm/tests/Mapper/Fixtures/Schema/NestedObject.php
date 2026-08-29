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
use CmsIg\Seal\Odm\Attribute\Identifier;
use CmsIg\Seal\Odm\Attribute\Index;

#[Index('nested')]
class NestedObject
{
    #[Identifier]
    public string $uuid;

    #[Field]
    public NestedSubObject $subObject;

    /**
     * @var array<NestedSubObject>
     */
    #[Field]
    public array $subObjects;

    /**
     * @param NestedSubObject[] $subObjects
     */
    public function __construct(
        string|null $uuid = null,
        NestedSubObject|null $subObject = null,
        array|null $subObjects = null,
    ) {
        if (null !== $uuid) {
            $this->uuid = $uuid;
        }
        if ($subObject instanceof NestedSubObject) {
            $this->subObject = $subObject;
        }
        if (null !== $subObjects) {
            $this->subObjects = $subObjects;
        }
    }
}
