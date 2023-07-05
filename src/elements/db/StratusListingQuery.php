<?php
namespace clickrain\stratus\elements\db;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class StratusListingQuery extends ElementQuery
{
    public $name;

    public $type;

    public $uuid;

    /**
     * @inheritdoc
     */
    protected array $defaultOrderBy = ['stratus_listings.name' => SORT_ASC];

    public function name($value)
    {
        $this->name = $value;

        return $this;
    }

    public function type($value)
    {
        $this->type = $value;

        return $this;
    }

    public function uuid($value)
    {
        $this->uuid = $value;

        return $this;
    }

    protected function beforePrepare(): bool
    {
        // join in the listings table
        $this->joinElementTable('stratus_listings');

        $this->query->select([
            'stratus_listings.name',
            'stratus_listings.type',
            'stratus_listings.address',
            'stratus_listings.address2',
            'stratus_listings.city',
            'stratus_listings.state',
            'stratus_listings.zip',
            'stratus_listings.timezone',
            'stratus_listings.phone',
            'stratus_listings.hours',
            'stratus_listings.holidayHours',
            'stratus_listings.reviewables',
            'stratus_listings.stratusUuid',
        ]);

        if ($this->name !== null) {
            $this->subQuery
                ->andWhere(Db::parseParam('stratus_listings.name', $this->name));
        }

        if ($this->type !== null) {
            $this->subQuery
                ->andWhere(Db::parseParam('stratus_listings.type', $this->type));
        }

        if ($this->uuid !== null) {
            $this->subQuery
                ->andWhere(Db::parseParam('stratus_listings.stratusUuid', $this->uuid));
        }

        return parent::beforePrepare();
    }
}
