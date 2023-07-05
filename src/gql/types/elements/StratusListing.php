<?php

namespace clickrain\stratus\gql\types\elements;

use clickrain\stratus\gql\interfaces\elements\StratusListing as StratusListingInterface;

class StratusListing extends \craft\gql\types\elements\Element
{
    public function __construct(array $config)
    {
        $config['interfaces'] = [
            StratusListingInterface::getType(),
        ];

        parent::__construct($config);
    }
}
