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
 * The document value of the identifier field should not contain spaces or special characters.
 * For example Meilisearch supports only characters (``a-z A-Z 0-9``), hyphens (``-``) and underscores (``_``).
 * Algolia does not support leading dash (``-``) or an apostrophe (``'``) characters.
 * To make switching search engines easier it is recommended to avoid any other special characters.
 *
 * @property false $multiple
 * @property false $searchable
 * @property true $filterable
 * @property true $sortable
 * @property false $distinct
 * @property false $facet
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
            distinct: false,
            facet: false,
            options: [],
        );
    }
}
