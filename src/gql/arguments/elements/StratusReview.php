<?php

namespace clickrain\stratus\gql\arguments\elements;

use GraphQL\Type\Definition\Type;

class StratusReview extends \craft\gql\base\ElementArguments
{
    public static function getArguments(): array
    {
        // append our argument to common element arguments and any from custom fields
        return array_merge(parent::getArguments(), self::getContentArguments(), [
            'listing' => [
                'name' => 'listing',
                'type' => Type::string(),
                'description' => 'Narrows query results based on parent listing.'
            ],
            'uuid' => [
                'name' => 'uuid',
                'type' => Type::string(),
                'description' => 'Narrows query results based on uuid.'
            ],
        ]);
    }
}