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

namespace CmsIg\Seal\Adapter\Loupe;

use CmsIg\Seal\Adapter\SearcherInterface;
use CmsIg\Seal\Adapter\StatisticsInterface;
use CmsIg\Seal\Marshaller\FlattenMarshaller;
use CmsIg\Seal\Schema\Index;
use CmsIg\Seal\Search\Condition;
use CmsIg\Seal\Search\Result;
use CmsIg\Seal\Search\Search;
use CmsIg\Seal\Statistics\Statistics;
use Loupe\Loupe\SearchParameters;

final class LoupeStatistics implements StatisticsInterface
{
    public function __construct(
        private readonly LoupeHelper $loupeHelper,
    ) {
    }

    public function getStatistics(Index $index): Statistics
    {
        $loupe = $this->loupeHelper->getLoupe($index);

        return new Statistics($loupe->countDocuments());
    }
}
