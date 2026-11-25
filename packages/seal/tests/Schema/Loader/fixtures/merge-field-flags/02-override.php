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

$fields = [];
foreach ([
    'text' => Field\TextField::class,
    'integer' => Field\IntegerField::class,
    'float' => Field\FloatField::class,
    'dateTime' => Field\DateTimeField::class,
] as $name => $class) {
    $fields[$name . 'Enabled'] = new $class($name . 'Enabled', distinct: true, facet: true, options: ['nested' => ['replace' => 'new']]);
    $fields[$name . 'Disabled'] = new $class($name . 'Disabled', distinct: false, facet: false);
}

$fields['object'] = new Field\ObjectField('object', [
    'value' => new Field\TextField('value', distinct: true, facet: true),
]);
$fields['typed'] = new Field\TypedField('typed', 'type', [
    'text' => ['value' => new Field\TextField('value', distinct: true, facet: true)],
]);

return new Index('flags', $fields);
