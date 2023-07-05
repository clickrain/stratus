<?php

namespace clickrain\stratus\gql\arguments\elements;

use GraphQL\Type\Definition\Type;

class StratusListing extends \craft\gql\base\ElementArguments
{
    /**
     * @inheritdoc
     */
    public static function getArguments(): array
    {
        // append our argument to common element arguments and any from custom fields
        return array_merge(parent::getArguments(), self::getContentArguments(), [
            'uuid' => [
                'name' => 'uuid',
                'type' => Type::string(),
                'description' => 'Narrows query results based on uuid.'
            ],
        ]);
    }
}