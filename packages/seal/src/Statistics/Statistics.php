<?php

namespace CmsIg\Seal\Statistics;

class Statistics
{
    public function __construct(private int $numberOfDocuments)
    {

    }

    public function getNumberOfDocuments(): int
    {
        return $this->numberOfDocuments;
    }
}