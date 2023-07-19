<?php

namespace clickrain\stratus\elements\conditions;

use craft\elements\conditions\ElementCondition;

/**
 * Review query condition.
 *
 * @author Joseph Marikle
 */
class StratusReviewCondition extends ElementCondition
{
    /**
     * @inheritdoc
     */
    protected function conditionRuleTypes(): array
    {
        return [
            StratusListingConditionRule::class
        ];
    }
}
