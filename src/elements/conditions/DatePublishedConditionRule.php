<?php

namespace clickrain\stratus\elements\conditions;

use Craft;
use craft\base\conditions\BaseDateRangeConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;

use clickrain\stratus\elements\db\StratusReviewQuery;
use clickrain\stratus\elements\StratusReviewElement;

/**
 * Date published condition rule.
 *
 * @author Joseph Marikle
 */
class DatePublishedConditionRule extends BaseDateRangeConditionRule implements ElementConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Craft::t('stratus', 'Date Published');
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return ['datePublished'];
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var StratusReviewQuery $query */
        $query->datePublished($this->queryParamValue());
    }

    /**
     * @inheritdoc
     */
    public function matchElement(ElementInterface $element): bool
    {
        /** @var StratusReviewElement $element */
        return $this->matchValue($element->platformPublishedDate);
    }
}
