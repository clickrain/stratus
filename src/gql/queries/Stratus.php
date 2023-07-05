<?php

namespace clickrain\stratus\gql\queries;

use GraphQL\Type\Definition\Type;
use clickrain\stratus\helpers\Gql as GqlHelper;
use clickrain\stratus\gql\interfaces\elements\StratusListing as StratusListingInterface;
use clickrain\stratus\gql\interfaces\elements\StratusReview as StratusReviewInterface;
use clickrain\stratus\gql\arguments\elements\StratusListing as StratusListingArguments;
use clickrain\stratus\gql\arguments\elements\StratusReview as StratusReviewArguments;
use clickrain\stratus\gql\resolvers\elements\StratusListing as StratusListingResolver;
use clickrain\stratus\gql\resolvers\elements\StratusReview as StratusReviewResolver;

class Stratus extends \craft\gql\base\Query
{
    public static function getQueries(bool $checkToken = true): array
    {
        $queries = [];

        // Make sure the current token’s schema allows querying stratus records
        if (!$checkToken || GqlHelper::canQueryStratusReviews()) {
            $queries = array_merge($queries, [
                'stratusReviews' => [
                    'type' => Type::listOf(StratusReviewInterface::getType()),
                    'args' => StratusReviewArguments::getArguments(),
                    'resolve' => StratusReviewResolver::class . '::resolve',
                    'description' => 'This query is used to query for Stratus reviews.'
                ],
                'stratusReview' => [
                    'type' => StratusReviewInterface::getType(),
                    'args' => StratusReviewArguments::getArguments(),
                    'resolve' => StratusReviewResolver::class . '::resolveOne',
                    'description' => 'This query is used to query for Stratus reviews.'
                ],
            ]);
        }


        // Make sure the current token’s schema allows querying stratus records
        if (!$checkToken || GqlHelper::canQueryStratusListings()) {
            $queries = array_merge($queries, [
                'stratusListings' => [
                    'type' => Type::listOf(StratusListingInterface::getType()),
                    'args' => StratusListingArguments::getArguments(),
                    'resolve' => StratusListingResolver::class . '::resolve',
                    'description' => 'This query is used to query for Stratus listings.'
                ],
                'stratusListing' => [
                    'type' => StratusListingInterface::getType(),
                    'args' => StratusListingArguments::getArguments(),
                    'resolve' => StratusListingResolver::class . '::resolveOne',
                    'description' => 'This query is used to query for Stratus listings.'
                ],
            ]);
        }

        // Provide one or more query definitions
        return $queries;
    }
}
