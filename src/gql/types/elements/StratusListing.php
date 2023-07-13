<?php

namespace clickrain\stratus\gql\types\elements;

use clickrain\stratus\gql\interfaces\elements\StratusListing as StratusListingInterface;
use clickrain\stratus\elements\StratusListingElement;
use craft\helpers\Json;
use GraphQL\Type\Definition\ResolveInfo;

class StratusListing extends \craft\gql\types\elements\Element
{
    public function __construct(array $config)
    {
        $config['interfaces'] = [
            StratusListingInterface::getType(),
        ];

        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    protected function resolve(mixed $source, array $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        /** @var StratusListingElement $source */
        $fieldName = $resolveInfo->fieldName;

        return match ($fieldName) {
            'hours', 'holidayHours', 'ratings' => $source->{$fieldName} ? Json::encode($source->{$fieldName}) : null,
            default => parent::resolve($source, $arguments, $context, $resolveInfo),
        };
    }
}
