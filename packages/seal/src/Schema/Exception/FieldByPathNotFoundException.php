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

namespace CmsIg\Seal\Schema\Exception;

final class FieldByPathNotFoundException extends \Exception
{
    public function __construct(string $indexName, string $path, \Throwable|null $previous = null)
    {
        parent::__construct(
            'Field path "' . $path . '" not found in index "' . $indexName . '"',
            0,
            $previous,
        );
    }
}
