<?php

namespace clickrain\stratus\gql\arguments\elements;

use clickrain\stratus\services\StratusService;
use clickrain\stratus\Stratus;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\Type;

class StratusReview extends \craft\gql\base\ElementArguments
{
    /** @var array<string, \GraphQL\Type\Definition\ScalarType> */
    protected static $customTypes;
    public const PLATFORM = 'platform';

    public static function getArguments(): array
    {
        if (! isset(static::$customTypes[self::PLATFORM])) {
            $service = Stratus::getInstance()->stratus;
            $platforms = $service->getPlatforms();

            static::$customTypes[self::PLATFORM] = new EnumType([
                'name' => 'StratusReviewPlatform',
                'description' => 'The platform the review was published on.',
                'values' => array_reduce(array_keys($platforms), function ($carry, $platform) use ($platforms) {
                    $name = $platforms[$platform];
                    $carry[$platform] = [
                        'value' => $platform,
                        'description' => "The {$name} platform."
                    ];
                    return $carry;
                }, [])
            ]);
        }

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
            'platform' => [
                'name' => 'platform',
                'type' => Type::listOf(static::$customTypes[self::PLATFORM]),
                'description' => 'Narrows query results based on platform.',
            ]
        ]);
    }
}