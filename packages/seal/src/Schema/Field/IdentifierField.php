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

namespace CmsIg\Seal\Schema\Field;

/**
 * Type to store the identifier this field type only exist once per index.
 *
 * @property false $multiple
 * @property false $searchable
 * @property true $filterable
 * @property true $sortable
 *
 * @readonly
 */
final class IdentifierField extends AbstractField
{
    public function __construct(string $name)
    {
        parent::__construct(
            $name,
            multiple: false,
            searchable: false,
            filterable: true,
            sortable: true,
            options: [],
        );
    }
}
