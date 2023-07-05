<?php

namespace clickrain\stratus\helpers;

class Gql extends \craft\helpers\Gql
{
    public static function canQueryStratusReviews(): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema();
        return isset($allowedEntities['stratus']);
    }

    public static function canQueryStratusListings(): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema();
        return isset($allowedEntities['stratus']);
    }
}
