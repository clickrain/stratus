<?php
namespace clickrain\stratus\elements;

use craft\base\Element;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Component;
use craft\helpers\Html;
use clickrain\stratus\elements\db\StratusListingQuery;
use clickrain\stratus\Stratus;
use Craft;
use craft\db\Query;
use craft\elements\db\EagerLoadPlan;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;

class StratusListingElement extends Element
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('stratus', 'Listing');
    }

    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return Craft::t('stratus', 'Listings');
    }

    /**
     * @inheritdoc
     */
    public static function hasContent(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function hasTitles(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public static function isLocalized(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public static function find(): ElementQueryInterface
    {
        return new StratusListingQuery(static::class);
    }

    /**
     * @var string Original name in Stratus
     */
    public $name;

    /**
     * @var string The type of reviable (either person or location)
     */
    public $type;

    /**
     * @var string Name of the person or location that was reviewed
     */
    public $reviewables;

    /**
     * @var string The unique identifier from stratus
     */
    public $stratusUuid;

    /**
     * @var string The address of the location
     */
    public $address;

    /**
     * @var string The address of the location
     */
    public $address2;

    /**
     * @var string The city of the location
     */
    public $city;

    /**
     * @var string The state of the location
     */
    public $state;

    /**
     * @var string The zip code of the location
     */
    public $zip;

    /**
     * @var string The timezone of the location
     */
    public $timezone;

    /**
     * @var string The phone number of the location
     */
    public $phone;

    /**
     * @var string The hours of the location
     */
    private $_hours;

    /**
     * @var string The holiday hours of the location
     */
    public $_holidayHours;

    /**
     * @var StratusReviewElement[]|null
     */
    private $_reviews;

    public function getHours(): array
    {
        return json_decode($this->_hours, true) ?: [];
    }

    public function setHours($hours): void
    {
        $this->_hours = $hours;
    }

    public function getHolidayHours(): array
    {
        return json_decode($this->_holidayHours, true) ?: [];
    }

    public function setHolidayHours($hours): void
    {
        $this->_holidayHours = $hours;
    }

    /**
     * get the ratings for all connected integrations
     *
     * @return array
     */
    public function getRatings(): array
    {
        $connections = json_decode($this->reviewables, true) ?: [];

        $connections = array_map(function($connection) {
            $values = array_filter(ArrayHelper::getColumn($connection['ratings'], 'value'));

            if (count($values) === 0) {
                return $connection + [
                    'avg' => null,
                    'max' => null,
                    'min' => null,
                    '_total' => 0,
                ];
            }

            return $connection + [
                'avg' => array_sum($values) / count($values),
                'max' => max($values),
                'min' => min($values),
                '_total' => count($values),
            ];
        }, $connections);

        return $connections;
    }

    /**
     * Get the average rating for all connected integrations
     *
     * @return float|null
     */
    public function getAvgRating(): ?float
    {
        $ratings = $this->getRatings();
        $totalRatings = array_sum(ArrayHelper::getColumn($ratings, '_total'));

        if ($totalRatings === 0) {
            return null;
        }

        return array_reduce($ratings, function($carry, $item) {
            return $carry + $item['_total'] * $item['avg'];
        }, 0) / $totalRatings;
    }

    /**
     * Get the maximum rating for all connected integrations
     *
     * @return float|null
     */
    public function getMaxRating(): ?float
    {
        $ratings = $this->getRatings();
        $maxRatings = ArrayHelper::getColumn($ratings, 'max');

        return count($maxRatings) ? max($maxRatings) : null;
    }

    /**
     * Get the address formatted for display as HTML
     *
     * @return string
     */
    public function getFullAddress(): string
    {
        return implode('<br>', array_filter([
            $this->address,
            $this->address2,
            implode(', ', array_filter([
                $this->city,
                $this->state,
                $this->zip,
            ])),
        ]));
    }

    /**
     * Get the hours formatted for display as HTML
     *
     * @return string
     */
    public function getFormattedHours(): string
    {
        $hours = json_decode($this->_hours, true) ?: [];

        $hours = array_map(function($day) {
            $hours = implode(' - ', array_filter([
                $day['open'] ?? null,
                $day['close'] ?? null,
            ]));

            return $day['closed'] ? 'Closed' : $hours;
        }, $hours);

        return implode('<br>', $hours);
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        $attributes = [
            'stratusUuid' => Craft::t('stratus', 'Stratus UUID'),
            'address' => Craft::t('stratus', 'Address'),
            'timezone' => Craft::t('stratus', 'Time Zone'),
            'hours' => Craft::t('stratus', 'Hours'),
            'holidayHours' => Craft::t('stratus', 'Holiday Hours'),
            'reviewables' => Craft::t('stratus', 'Ratings'),
        ];

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        $tableAttributes = array_keys(static::defineTableAttributes());
        $excludeByDefault = [
            'timezone',
            'stratusUuid',
            'holidayHours'
        ];

        // return all except for the UUIDs
        return array_filter($tableAttributes, function($attribute) use ($excludeByDefault) {
            return !in_array($attribute, $excludeByDefault);
        });
    }

    /**
     * @inheritdoc
     */
    protected function attributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'name':
                return $this->name;
            case 'type':
                return $this->type;
            case 'address':
                return $this->getFullAddress();
            case 'phone':
                return $this->phone ?? '';
            case 'timezone':
                return $this->timezone ?? '';
            case 'hours':
                // formatted hours
                try {
                    $hours = json_decode($this->_hours, true);
                } catch (\Exception $e) {
                    $hours = [];
                }

                $hours = $hours ?: [];

                return array_reduce(array_keys($hours), function ($result, $day) use ($hours) {
                    $result .= $day . ': ';
                    if ($hours[$day]['closed']) {
                        $result .= 'Closed';
                    } else if ($hours[$day]['24hr']) {
                        $result .= 'Open 24 Hours';
                    } else {
                        $result .= implode(', ', array_map(function ($period) {
                            $open = date('g:i A', strtotime($period['open']));
                            $close = date('g:i A', strtotime($period['close']));
                            return $open . ' - ' . $close;
                        }, $hours[$day]['periods'] ?: []));
                    }
                    $result .= '<br>';
                    return $result;
                }, '');
            case 'holidayHours':
                return implode('<br>', array_map(function($day) {
                    $name = $day['holiday'] === 'custom' ? date('F j, Y', strtotime($day['date'])) : $day['name'];

                    if ($day['closed']) {
                        return $name . ': Closed';
                    }

                    if ($day['24hr']) {
                        return $name . ': Open 24 Hours';
                    }

                    return $name . ': ' . implode(', ', array_map(function ($period) {
                        $open = date('g:i A', strtotime($period['open']));
                        $close = date('g:i A', strtotime($period['close']));
                        return $open . ' - ' . $close;
                    }, $day['periods'] ?: []));
                }, $this->getHolidayHours()));
            case 'reviewables':

                $platforms = [
                    'google',
                    'facebook',
                    'healthgrades',
                    'google_play_store',
                    'apple_app_store',
                    'yelp',
                    'tripadvisor',
                    'bbb',
                    'indeed',
                    'glassdoor',
                    'yellow_pages',
                    'zocdoc',
                    'vitals',
                    'realself',
                    'ratemds',
                    'webmd',
                    'zillow'
                ];

                return Craft::$app->getView()->renderTemplate('stratus/_components/RatingsSummary', [
                    'reviewables' => json_decode($this->reviewables, true),
                    'icons' => array_combine(
                        $platforms,
                        array_map(function($platform) {
                            return Html::tag('span', Cp::iconSvg("@clickrain/stratus/assetbundles/stratus/dist/img/{$platform}-icon.svg", 'rating'), [
                                'class' => ['stratus-icon'],
                            ]);
                        }, $platforms)
                    )
                ]);
        }

        return parent::attributeHtml($attribute);
    }

    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        return [
            [
                'label' => Craft::t('stratus', 'Name'),
                'orderBy' => 'stratus_listings.name',
                'attribute' => 'name',
            ],
            [
                'label' => Craft::t('stratus', 'Address'),
                'orderBy' => 'stratus_listings.address',
                'attribute' => 'address',
            ],
            [
                'label' => Craft::t('stratus', 'City'),
                'orderBy' => 'stratus_listings.city',
                'attribute' => 'city',
            ],
            [
                'label' => Craft::t('stratus', 'State'),
                'orderBy' => 'stratus_listings.state',
                'attribute' => 'state',
            ],
            [
                'label' => Craft::t('stratus', 'Zip'),
                'orderBy' => 'stratus_listings.zip',
                'attribute' => 'zip',
            ],
            [
                'label' => Craft::t('stratus', 'Phone'),
                'orderBy' => 'stratus_listings.phone',
                'attribute' => 'phone',
            ],
        ];
    }

    public function afterSave(bool $isNew): void
    {
        if ($isNew) {
            Craft::$app->db->createCommand()
                ->insert('{{%stratus_listings}}', [
                    'id' => $this->id,
                    'name' => $this->name,
                    'type' => $this->type,
                    'address' => $this->address,
                    'address2' => $this->address2,
                    'city' => $this->city,
                    'state' => $this->state,
                    'zip' => $this->zip,
                    'timezone' => $this->timezone,
                    'phone' => $this->phone,
                    'hours' => $this->_hours ? json_encode($this->_hours) : null,
                    'holidayHours' => $this->_holidayHours ? json_encode($this->_holidayHours) : null,
                    'reviewables' => $this->reviewables ? json_encode($this->reviewables) : null,
                    'stratusUuid' => $this->stratusUuid,
                ])
                ->execute();
        } else {
            Craft::$app->db->createCommand()
                ->update('{{%stratus_listings}}', [
                    'name' => $this->name,
                    'type' => $this->type,
                    'address' => $this->address,
                    'address2' => $this->address2,
                    'city' => $this->city,
                    'state' => $this->state,
                    'zip' => $this->zip,
                    'timezone' => $this->timezone,
                    'phone' => $this->phone,
                    'hours' => $this->_hours ? json_encode($this->_hours) : null,
                    'holidayHours' => $this->_holidayHours ? json_encode($this->_holidayHours) : null,
                    'reviewables' => $this->reviewables ? json_encode($this->reviewables) : null,
                    'stratusUuid' => $this->stratusUuid,
                ], ['id' => $this->id])
                ->execute();
        }

        parent::afterSave($isNew);
    }

    public function afterDelete(): void
    {
        Craft::$app->db->createCommand()
            ->softDelete('{{%stratus_listings}}', [
                'id' => $this->id,
            ])
            ->execute();

        parent::afterDelete();
    }

    protected static function defineSearchableAttributes(): array
    {
        return ['name', 'stratusUuid'];
    }

    protected static function defineSources(string $context = null): array
    {
        $sources = [
            [
                'key' => '*',
                'label' => 'All Listings',
                'criteria' => [],
                'defaultSort' => ['name', 'asc'],
            ],
        ];

        $sources[] = ['heading' => Craft::t('stratus', 'By Type')];
        $sources[] = [
            'key' => "type:location",
            'label' => Craft::t('stratus', 'Location'),
            'criteria' => [
                'type' => 'location',
            ],
            'defaultSort' => ['platformPublishedDate', 'desc'],
        ];
        $sources[] = [
            'key' => "type:person",
            'label' => Craft::t('stratus', 'Person'),
            'criteria' => [
                'type' => 'person',
            ],
            'defaultSort' => ['platformPublishedDate', 'desc'],
        ];
        $sources[] = [
            'key' => "type:app",
            'label' => Craft::t('stratus', 'App'),
            'criteria' => [
                'type' => 'app',
            ],
            'defaultSort' => ['platformPublishedDate', 'desc'],
        ];

        return $sources;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['name', 'type', 'stratusUuid'], 'required'];

        return $rules;
    }

    /**
     * Returns what the element should be called within the control panel.
     *
     * @return string|null
     * @since 3.6.4
     */
    protected function uiLabel(): ?string
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    protected function isEditable(): bool
    {
        return false;
    }

    public static function gqlTypeNameByContext(mixed $context): string
    {
        return 'StratusListing';
    }

    /**
     * @inheritdoc
     */
    public function setEagerLoadedElements(string $handle, array $elements, EagerLoadPlan $plan): void
    {
        if ($handle === 'reviews') {
            $reviews = $elements ?: null;
            $this->setReviews($reviews);
        } else {
            parent::setEagerLoadedElements($handle, $elements, $plan);
        }
    }

    /**
     * @inheritdoc
     */
    public static function eagerLoadingMap(array $sourceElements, string $handle): array|null|false
    {
        if ($handle === 'reviews') {
            // Get the source element IDs
            $sourceElementParentIds = ArrayHelper::getColumn($sourceElements, 'stratusUuid');

            $targets = (new Query())
                ->select(['id', 'stratusParentUuid'])
                ->from(['{{%stratus_reviews}}'])
                ->where(['stratusParentUuid' => $sourceElementParentIds])
                ->all();

            return [
                'elementType' => StratusReviewElement::class,
                'map' => array_map(function($target) use ($sourceElements) {
                    $source = ArrayHelper::firstWhere($sourceElements, 'stratusUuid', $target['stratusParentUuid']);

                    return [
                        'source' => $source->id,
                        'target' => $target['id'],
                    ];
                }, $targets),
            ];
        }

        return parent::eagerLoadingMap($sourceElements, $handle);
    }

    /**
     * @return StratusReviewElement|null
     */
    public function getReviews()
    {
        if ($this->_reviews === null) {
            $this->_reviews = Stratus::getInstance()->stratus->getReviews([
                'listing' => $this->stratusUuid
            ]) ?? false;
        }

        return $this->_reviews ?: null;
    }

    /**
     * @param StratusReviewElement[]|null $listing
     */
    public function setReviews(array $listing = null)
    {
        $this->_reviews = $listing;
    }

    public function getDetails(): array
    {
        return [
            'Listing Details' => [
                'Name' => $this->name,
                'Address' => $this->attributeHtml('address'),
                'Phone' => $this->phone,
                'Time Zone' => $this->timezone,
                'Hours' => $this->attributeHtml('hours'),
                'Holiday Hours' => $this->attributeHtml('holidayHours'),
            ],
            'System Details' => [
                'Craft ID' => $this->id,
                'Craft UID' => $this->uid,
                'Stratus UUID' => $this->stratusUuid,
                'Type' => $this->type,
            ],
        ];
    }
}
