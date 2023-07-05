<?php

namespace clickrain\stratus\gql\interfaces\elements;

use clickrain\stratus\gql\types\generators\StratusReviewType;
use clickrain\stratus\helpers\Gql;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\InterfaceType;
use craft\gql\GqlEntityRegistry;
use craft\gql\types\DateTime;

use Craft;

class StratusReview extends \craft\gql\interfaces\Element
{
    public static function getName(): string
    {
        return 'StratusReviewInterface';
    }

    public static function getTypeGenerator(): string
    {
        return StratusReviewType::class;
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
            'description' => 'This is the interface implemented by all Stratus reviews.',
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
                'content' => [
                    'name' => 'content',
                    'type' => Type::string(),
                    'description' => 'The review content.'
                ],
                'platform' => [
                    'name' => 'platform',
                    'type' => Type::string(),
                    'description' => '',
                ],
                'platformName' => [
                    'name' => 'platformName',
                    'type' => Type::string(),
                    'description' => '',
                ],
                'rating' => [
                    'name' => 'rating',
                    'type' => Type::float(),
                    'description' => '',
                ],
                'recommends' => [
                    'name' => 'recommends',
                    'type' => Type::boolean(),
                    'description' => '',
                ],
                'author' => [
                    'name' => 'author',
                    'type' => Type::string(),
                    'description' => '',
                ],
                'platformPublishedDate' => [
                    'name' => 'platformPublishedDate',
                    'type' => DateTime::getType(),
                    'description' => '',
                ],
                'reviewableType' => [
                    'name' => 'reviewableType',
                    'type' => Type::string(),
                    'description' => '',
                ],
                'reviewableName' => [
                    'name' => 'reviewableName',
                    'type' => Type::string(),
                    'description' => '',
                ],
                'stratusUuid' => [
                    'name' => 'stratusUuid',
                    'type' => Type::string(),
                    'description' => '',
                ],
                'stratusParentUuid' => [
                    'name' => 'stratusParentUuid',
                    'type' => Type::string(),
                    'description' => '',
                ],
                'listing' => [
                    'name' => 'listing',
                    'type' => StratusListing::getType(),
                    'description' => '',
                    'complexity' => Gql::eagerLoadComplexity(),
                ],
            ]
        ), self::getName());
    }
}
