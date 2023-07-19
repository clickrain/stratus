<?php

namespace clickrain\stratus\elements\conditions;

use Craft;
use craft\base\conditions\BaseElementSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\conditions\ElementConditionRuleInterface;
use craft\elements\db\ElementQueryInterface;

use clickrain\stratus\elements\db\StratusReviewQuery;
use clickrain\stratus\elements\StratusListingElement;

/**
 * Listing query condition.
 *
 * @author Joseph Marikle
 */
class StratusListingConditionRule extends BaseElementSelectConditionRule implements ElementConditionRuleInterface
{
    /**
     * @inheritdoc
     */
    protected function elementType(): string
    {
        return StratusListingElement::class;
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return Craft::t('app', 'Listing');
    }

    /**
     * @inheritdoc
     */
    public function getExclusiveQueryParams(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function modifyQuery(ElementQueryInterface $query): void
    {
        /** @var StratusReviewQuery $query */
        $query->listingId($this->getElementId());
        //$query->countryCode($this->paramValue());
    }

    /**
     * @inheritdoc
     */
    public function matchElement(ElementInterface $element): bool
    {
        /** @var StratusListingElement $element */
        return false;
        //return $this->matchValue($element->countryCode);
    }
}
