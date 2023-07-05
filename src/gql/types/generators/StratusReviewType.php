<?php
namespace clickrain\stratus\gql\types\generators;

use clickrain\stratus\elements\StratusReviewElement;
use clickrain\stratus\gql\types\elements\StratusReview;
use clickrain\stratus\gql\interfaces\elements\StratusReview as StratusReviewInterface;
use craft\gql\base\GeneratorInterface;
use craft\gql\GqlEntityRegistry;
use craft\gql\types\elements\Element;

use Craft;

class StratusReviewType implements GeneratorInterface
{
    public static function generateTypes(mixed $context = null): array
    {
        $type = static::generateType($context);
        return [$type->name => $type];
    }

    public static function generateType($context): Element
    {
        $pluginType = new StratusReviewElement();

        $typeName = $pluginType->getGqlTypeName();
        /** @var \craft\services\Gql */
        $gql = Craft::$app->getGql();
        $reviewFields = $gql->prepareFieldDefinitions(
            StratusReviewInterface::getFieldDefinitions(),
            $typeName
        );

        // Return the type if it exists, otherwise create and return it
        return GqlEntityRegistry::getEntity($typeName) ?:
            GqlEntityRegistry::createEntity(
                $typeName,
                new StratusReview([
                    'name' => $typeName,
                    'fields' => function() use ($reviewFields) {
                        return $reviewFields;
                    },
                ])
            );
    }
}