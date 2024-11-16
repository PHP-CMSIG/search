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

return new Index('blog', [
    'id' => new Field\IdentifierField('id'),
    'title' => new Field\TextField('title'),
    'description' => new Field\TextField('description'),
]);
