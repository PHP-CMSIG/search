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

namespace CmsIg\Seal\Schema\Loader;

use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Schema\Schema;

final class PrefixLoader implements LoaderInterface
{
    public function __construct(
        private readonly LoaderInterface $loader,
        private readonly string $indexNamePrefix,
    ) {
    }

    public function load(): Schema
    {
        $schema = $this->loader->load();
        $indexes = [];

        foreach ($schema->indexes as $name => $index) {
            $indexes[$name] = new Index($this->indexNamePrefix . $index->name, $index->fields, $index->options);
        }

        return new Schema($indexes);
    }
}
