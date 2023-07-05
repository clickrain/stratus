<?php

namespace clickrain\stratus\gql\types\elements;

use clickrain\stratus\gql\interfaces\elements\StratusReview as StratusReviewInterface;

class StratusReview extends \craft\gql\types\elements\Element
{
    public function __construct(array $config)
    {
        $config['interfaces'] = [
            StratusReviewInterface::getType(),
        ];

        parent::__construct($config);
    }
}
