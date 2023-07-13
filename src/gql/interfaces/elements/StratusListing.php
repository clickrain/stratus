<?php

namespace clickrain\stratus\gql\interfaces\elements;

use clickrain\stratus\gql\types\generators\StratusListingType;
use clickrain\stratus\helpers\Gql;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\InterfaceType;
use craft\gql\GqlEntityRegistry;

use Craft;

class StratusListing extends \craft\gql\interfaces\Element
{
    public static function getName(): string
    {
        return 'StratusListingInterface';
    }

    public static function getTypeGenerator(): string
    {
        return StratusListingType::class;
    }

    public static function getType($fields = null): Type
    {
        // Return the type if it’s already been created
        if ($type = GqlEntityRegistry::getEntity(self::getName())) {
            return $type;
        }

        // Otherwise create the type via the entity registry, which handles prefixing
        $type = GqlEntityRegistry::createEntity(self::getName(), new InterfaceType([
            'name' => static::getName(),
            'fields' => self::class . '::getFieldDefinitions',
            'description' => 'This is the interface implemented by all Stratus listings.',
            'resolveType' => self::class . '::resolveElementTypeName',
        ]));

        return $type;
    }

    public static function getFieldDefinitions(): array
    {
        // Add our custom widget’s field to common ones for all elements
        /** @var \craft\services\Gql */
        $gql = Craft::$app->getGql();
        return $gql->prepareFieldDefinitions(array_merge(
            parent::getFieldDefinitions(),
            [
                'name' => [
                    'name' => 'name',
                    'type' => Type::string(),
                    'description' => 'The listing name.'
                ],
                'type' => [
                    'name' => 'type',
                    'type' => Type::string(),
                    'description' => 'The type of Stratus listing.'
                ],
                'address' => [
                    'name' => 'address',
                    'type' => Type::string(),
                    'description' => 'The listing address.'
                ],
                'address2' => [
                    'name' => 'address2',
                    'type' => Type::string(),
                    'description' => 'The listing address2.'
                ],
                'city' => [
                    'name' => 'city',
                    'type' => Type::string(),
                    'description' => 'The listing city.'
                ],
                'state' => [
                    'name' => 'state',
                    'type' => Type::string(),
                    'description' => 'The listing state.'
                ],
                'zip' => [
                    'name' => 'zip',
                    'type' => Type::string(),
                    'description' => 'The listing zip.'
                ],
                'phone' => [
                    'name' => 'phone',
                    'type' => Type::string(),
                    'description' => 'The listing phone.'
                ],
                'timezone' => [
                    'name' => 'timezone',
                    'type' => Type::string(),
                    'description' => 'The listing timezone.'
                ],
                'stratusUuid' => [
                    'name' => 'stratusUuid',
                    'type' => Type::string(),
                    'description' => '',
                ],
                'maxRating' => [
                    'name' => 'maxRating',
                    'type' => Type::float(),
                    'description' => 'Highest review rating',
                ],
                'avgRating' => [
                    'name' => 'avgRating',
                    'type' => Type::float(),
                    'description' => 'Average review rating',
                ],
                'ratings' => [
                    'name' => 'ratings',
                    'type' => Type::string(),
                    'description' => 'Rating data as JSON',
                ],
                'reviews' => [
                    'name' => 'reviews',
                    'type' => Type::listOf(StratusReview::getType()),
                    'description' => '',
                    'complexity' => Gql::eagerLoadComplexity(),
                ],
                'hours' => [
                    'name' => 'hours',
                    'type' => Type::string(),
                    'description' => ''
                ],
                'holidayHours' => [
                    'name' => 'holidayHours',
                    'type' => Type::string(),
                    'description' => ''
                ],
            ]
        ), self::getName());
    }
}
