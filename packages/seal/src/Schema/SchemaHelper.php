<?php

namespace CmsIg\Seal\Schema;

class SchemaHelper
{
    public static function validateDistinctFieldsAreFilterable(Index $index): void
    {
        foreach ($index->fields as $field) {
            if ($field->distinct && !$field->filterable) {
                throw new \LogicException(sprintf('The distinct attribute "%s" has to be filterable.', $field->name));
            }
        }
    }
}