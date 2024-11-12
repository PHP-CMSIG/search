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

namespace CmsIg\Seal\Adapter;

use CmsIg\Seal\Search\Result;
use CmsIg\Seal\Search\Search;

interface SearcherInterface
{
    public function search(Search $search): Result;
}
