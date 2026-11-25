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

use CmsIg\Seal\Schema\Field;
use CmsIg\Seal\Schema\Index;

return new Index('boolean', [
    'enabled' => new Field\BooleanField('enabled', multiple: false, filterable: false, sortable: false, distinct: false, facet: false, options: ['nested' => ['keep' => true, 'replace' => 'old']]),
    'disabled' => new Field\BooleanField('disabled', multiple: true, filterable: true, sortable: true, distinct: true, facet: true),
]);
