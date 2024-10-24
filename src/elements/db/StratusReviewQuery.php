<?php
namespace clickrain\stratus\elements\db;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class StratusReviewQuery extends ElementQuery
{
    public $platform;

    public $platforms;

    public $rating;

    public $recommends;

    public $type;

    public $listing;

    public $listingId;

    public $uuid;

    public $datePublished;

    public $reviewContent;

    public $author;

    /**
     * @inheritdoc
     */
    protected array $defaultOrderBy = ['stratus_reviews.platformPublishedDate' => SORT_DESC];

    public function platform($value)
    {
        $this->platform = $value;

        return $this;
    }

    public function platforms($value)
    {
        $this->platforms = $value;

        return $this;
    }

    public function rating($value)
    {
        $this->rating = $value;

        return $this;
    }

    public function recommends($value)
    {
        $this->recommends = $value;

        return $this;
    }

    public function type($value)
    {
        $this->type = $value;

        return $this;
    }

    public function listing($value)
    {
        $this->listing = $value;

        return $this;
    }

    public function listingId($value)
    {
        $this->listingId = $value;

        return $this;
    }

    public function uuid($value)
    {
        $this->uuid = $value;

        return $this;
    }

    /**
     * @return static
     */
    public function datePublished(mixed $value): self
    {
        $this->datePublished = $value;
        return $this;
    }

    protected function beforePrepare(): bool
    {
        // join in the reviews table
        $this->joinElementTable('stratus_reviews');
        $this->subQuery->innerJoin(['stratus_listings' => '{{%stratus_listings}}'], '[[stratus_listings.stratusUuid]] = [[stratus_reviews.stratusParentUuid]]');
        $this->query->innerJoin(['stratus_listings' => '{{%stratus_listings}}'], '[[stratus_listings.stratusUuid]] = [[stratus_reviews.stratusParentUuid]]');

        $this->query->select([
            'stratus_reviews.platform',
            'stratus_reviews.platformName',
            'stratus_reviews.rating',
            'stratus_reviews.recommends',
            'stratus_reviews.reviewContent',
            'stratus_reviews.author',
            'stratus_reviews.platformPublishedDate',
            'stratus_reviews.reviewableType',
            'stratus_reviews.reviewableName',
            'stratus_reviews.stratusUuid',
            'stratus_reviews.stratusParentUuid',
        ]);

        if ($this->platform !== null) {
            $this->subQuery
                ->andWhere(Db::parseParam('stratus_reviews.platform', $this->platform));
        }

        if ($this->platforms !== null) {
            $this->subQuery
                ->andWhere(Db::parseParam('stratus_reviews.platform', $this->platforms));
        }

        if ($this->rating !== null || $this->recommends !== null) {
            $this->subQuery
                ->andWhere([
                    'or',
                    Db::parseParam('stratus_reviews.rating', $this->rating),
                    Db::parseParam('stratus_reviews.recommends', $this->recommends, '=', false, \yii\db\Schema::TYPE_BOOLEAN),
                ]);
        }

        if ($this->type !== null) {
            $this->subQuery
                ->andWhere(Db::parseParam('stratus_reviews.reviewableType', $this->type));
        }

        if ($this->listing !== null) {
            $this->subQuery
                ->andWhere(Db::parseParam('stratus_reviews.stratusParentUuid', $this->listing));
        }

        if ($this->listingId !== null) {
            $this->subQuery
                ->andWhere(Db::parseParam('stratus_listings.id', $this->listingId));
        }

        if ($this->uuid !== null) {
            $this->subQuery
                ->andWhere(Db::parseParam('stratus_reviews.stratusUuid', $this->uuid));
        }

        if ($this->datePublished) {
            $this->subQuery
                ->andWhere(Db::parseDateParam('stratus_reviews.platformPublishedDate', $this->datePublished));
        }

        if ($this->reviewContent !== null) {
            $this->subQuery
                ->andWhere(Db::parseParam('stratus_reviews.reviewContent', $this->reviewContent));
        }

        if ($this->author !== null) {
            $this->subQuery
                ->andWhere(Db::parseParam('stratus_reviews.author', $this->author));
        }

        return parent::beforePrepare();
    }
}
