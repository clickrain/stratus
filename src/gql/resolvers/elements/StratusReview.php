<?php

namespace clickrain\stratus\gql\resolvers\elements;

use clickrain\stratus\elements\StratusReviewElement;
use clickrain\stratus\helpers\Gql as GqlHelper;

class StratusReview extends \craft\gql\base\ElementResolver
{
    public static function prepareQuery(mixed $source, array $arguments, ?string $fieldName = null): mixed
    {
        if ($source === null) {
            // If this is the beginning of a resolver chain, start fresh
            $query = StratusReviewElement::find();
        } else {
            // If not, get the prepared element query
            $query = $source->$fieldName;
        }

        // Return the query if it’s preloaded
        if (is_array($query)) {
            return $query;
        }

        foreach ($arguments as $key => $value) {
            if (method_exists($query, $key)) {
                $query->$key($value);
            } elseif (property_exists($query, $key)) {
                $query->$key = $value;
            } else {
                // Catch custom field queries
                $query->$key($value);
            }
        }

        // Don’t return anything that’s not allowed
        if (!GqlHelper::canQueryStratusReviews()) {
            return [];
        }

        return $query;
    }
}
