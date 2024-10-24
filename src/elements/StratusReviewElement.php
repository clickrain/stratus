<?php
namespace clickrain\stratus\elements;

use clickrain\stratus\elements\conditions\StratusReviewCondition;
use craft\base\Element;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Component;
use craft\helpers\Db;
use craft\helpers\Html;
use craft\validators\DateTimeValidator;
use LitEmoji\LitEmoji;
use clickrain\stratus\elements\db\StratusReviewQuery;
use clickrain\stratus\Stratus;
use Craft;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\db\EagerLoadPlan;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\helpers\Cp;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use DateTime;
use yii\db\Expression;
use yii\db\Query;

class StratusReviewElement extends Element
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('stratus', 'Review');
    }

    /**
     * @inheritdoc
     */
    public static function pluralDisplayName(): string
    {
        return Craft::t('stratus', 'Reviews');
    }

    /**
     * @inheritdoc
     */
    public static function hasContent(): bool
    {
        return true;
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
        return new StratusReviewQuery(static::class);
    }

    /**
     * @inheritdoc
     */
    public static function createCondition(): ElementConditionInterface
    {
        return Craft::createObject(StratusReviewCondition::class, [static::class]);
    }


    /**
     * @var string Original platform identifier
     */
    public $platform;

    /**
     * @var string Original platform nice name
     */
    public $platformName;

    /**
     * @var int|null Rating out of five stars
     */
    public $rating;

    /**
     * @var bool|null Recommendation of positive or negative
     */
    public $recommends;

    /**
     * @var string|null Author of the original review (may be generic)
     */
    public $author;

    /**
     * @var DateTime Timestamp as provided by the platform (or guessed)
     */
    public ?DateTime $platformPublishedDate;

    /**
     * @var string The type of reviable (either person or location)
     */
    public $reviewableType;

    /**
     * @var string Name of the person or location that was reviewed
     */
    public $reviewableName;

    /**
     * @var string The unique identifier from stratus
     */
    public $stratusUuid;

    /**
     * @var string The unique identifier of the reviewable parent from stratus
     */
    public $stratusParentUuid;

    /**
     * @var string|null The review text
     */
    public $reviewContent;

    /**
     * @var StratusListingElement|null
     */
    private $_listing;

    protected static function defineSources(string $context = null): array
    {
        /** @var \stratus\services\StratusService */
        $service = Stratus::getInstance()->stratus;

        $sources = [
            [
                'key' => '*',
                'label' => 'All Reviews',
                'criteria' => [],
                'defaultSort' => ['platformPublishedDate', 'desc'],
            ],
        ];

        $sources[] = ['heading' => \Craft::t('stratus', 'By Platform')];

        foreach (Stratus::getInstance()->stratus->getPlatformIdentifiers() as $platform) {
            $sources[] = [
                'key' => "platform:$platform",
                'label' => $service->getPlatformName($platform),
                'criteria' => [
                    'platform' => $platform,
                ],
                'defaultSort' => ['platformPublishedDate', 'desc'],
            ];
        }

        $sources[] = ['heading' => \Craft::t('stratus', 'By Type')];
        $sources[] = [
            'key' => "type:location",
            'label' => \Craft::t('stratus', 'Location'),
            'criteria' => [
                'type' => 'location',
            ],
            'defaultSort' => ['platformPublishedDate', 'desc'],
        ];
        $sources[] = [
            'key' => "type:person",
            'label' => \Craft::t('stratus', 'Person'),
            'criteria' => [
                'type' => 'person',
            ],
            'defaultSort' => ['platformPublishedDate', 'desc'],
        ];
        $sources[] = [
            'key' => "type:app",
            'label' => \Craft::t('stratus', 'App'),
            'criteria' => [
                'type' => 'app',
            ],
            'defaultSort' => ['platformPublishedDate', 'desc'],
        ];

        $sources[] = ['heading' => \Craft::t('stratus', 'By Rating')];
        foreach ([ 5, 4, 3, 2, 1 ] as $rating) {
            $sources[] = [
                'key' => "rating:$rating",
                'label' => \Craft::t('stratus', $rating . ' Star'),
                'criteria' => [
                    'rating' => $rating,
                ],
                'defaultSort' => ['platformPublishedDate', 'desc'],
            ];
        }
        $sources[] = [
            'key' => "rating:recommends",
            'label' => \Craft::t('stratus', 'Recommends'),
            'criteria' => [
                'recommends' => true,
            ],
            'defaultSort' => ['platformPublishedDate', 'desc'],
        ];
        $sources[] = [
            'key' => "rating:not_recommends",
            'label' => \Craft::t('stratus', 'Not Recommends'),
            'criteria' => [
                'recommends' => false,
            ],
            'defaultSort' => ['platformPublishedDate', 'desc'],
        ];

        return $sources;
    }

    protected static function defineFieldLayouts(?string $source): array
    {
        return Craft::$app->getFields()->getLayoutsByType(static::class);
    }

    protected static function defineSearchableAttributes(): array
    {
        return ['platformName', 'author', 'reviewContent', 'reviewableName'];
    }

    /**
     * @inheritdoc
     */
    protected static function defineSortOptions(): array
    {
        return [
            [
                'label' => \Craft::t('stratus', 'Date Published'),
                'orderBy' => 'stratus_reviews.platformPublishedDate',
                'attribute' => 'platformPublishedDate',
                'defaultDir' => 'desc',
            ],
            [
                'label' => \Craft::t('stratus', 'Listing Name'),
                'orderBy' => ['stratus_listings.name', '[[platformPublishedDate]] DESC'],
                'attribute' => 'listing',
                'defaultDir' => 'desc',
            ],
            [
                'label' => \Craft::t('stratus', 'Platform'),
                'orderBy' => ['stratus_reviews.platform', '[[platformPublishedDate]] DESC'],
                'attribute' => 'platform',
            ],
            [
                'label' => \Craft::t('stratus', 'Rating'),
                'orderBy' => function(int $dir) {
                    if ($dir === SORT_ASC) {
                        return new Expression('COALESCE([[rating]], CASE [[recommends]] WHEN 0 THEN 1 WHEN 1 THEN 5 END) ASC, [[platformPublishedDate]] DESC');
                    }

                    return new Expression('COALESCE([[rating]], CASE [[recommends]] WHEN 0 THEN 1 WHEN 1 THEN 5 END) DESC, [[platformPublishedDate]] DESC');
                },
                'attribute' => 'rating',
                'defaultDir' => 'desc',
            ],
            [
                'label' => \Craft::t('stratus', 'Author'),
                'orderBy' => ['stratus_reviews.author', '[[platformPublishedDate]] DESC'],
                'attribute' => 'author',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected static function defineTableAttributes(): array
    {
        $attributes = [
            'stratusUuid' => Craft::t('stratus', 'Stratus UUID'),
            'listing' => \Craft::t('stratus', 'Listing Name'),
            'platformPublishedDate' => \Craft::t('stratus', 'Published Date'),
            'platform' => \Craft::t('stratus', 'Platform'),
            'author' => \Craft::t('stratus', 'Author'),
            'reviewContent' => \Craft::t('stratus', 'Review Text'),
            'rating' => \Craft::t('stratus', 'Rating'),
        ];

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        $tableAttributes = array_keys(static::defineTableAttributes());

        // return all except for the UUIDs
        return array_filter($tableAttributes, function($attribute) {
            return !in_array($attribute, ['stratusUuid']);
        });
    }

    /**
     * @inheritdoc
     */
    public static function eagerLoadingMap(array $sourceElements, string $handle): array|null|false
    {
        if ($handle === 'listing') {
            // Get the source element IDs
            $sourceElementParentIds = ArrayHelper::getColumn($sourceElements, 'stratusParentUuid');

            $targets = (new Query())
                ->select(['id', 'stratusUuid'])
                ->from(['{{%stratus_listings}}'])
                ->where(['stratusUuid' => $sourceElementParentIds])
                ->all();

            return [
                'elementType' => StratusListingElement::class,
                'map' => array_map(function($target) use ($sourceElements) {
                    $source = ArrayHelper::firstWhere($sourceElements, 'stratusParentUuid', $target['stratusUuid']);

                    return [
                        'source' => $source->id,
                        'target' => $target['id'],
                    ];
                }, $targets),
            ];
        }

        return parent::eagerLoadingMap($sourceElements, $handle);
    }

    public static function gqlTypeNameByContext(mixed $context): string
    {
        return 'StratusReview';
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['platform', 'platformPublishedDate', 'reviewableName', 'reviewableType'], 'required'];
        $rules[] = [['platformPublishedDate'], DateTimeValidator::class];

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
        $date = Craft::$app->getFormatter()->asDate(
            $this->platformPublishedDate,
            'MMM d yyyy'
        );

        return $this->title ?: "$date "
            . ($this->recommends !== null ? ($this->recommends ? 'recommended' : 'not recommended') : '')
            . ($this->recommends === null ? $this->rating . ' star rating' : '') .
            " {$this->platformName} review by {$this->author}";
    }

    /**
     * @inheritdoc
     */
    public function canDelete(User $user): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function canView(User $user): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function canSave(User $user): bool
    {
        return false;
    }

    public function getFieldLayout(): ?FieldLayout
    {
        $layoutElements = [
        ];

        $fieldLayout = new FieldLayout();

        $tab = new FieldLayoutTab();
        $tab->name = 'Content';
        $tab->setLayout($fieldLayout);
        $tab->setElements($layoutElements);

        $fieldLayout->setTabs([ $tab ]);

        return $fieldLayout;
    }

    /**
     * @inheritdoc
     */
    public function setEagerLoadedElements(string $handle, array $elements, EagerLoadPlan $plan): void
    {
        if ($handle === 'listing') {
            $listing = $elements[0] ?? null;
            $this->setListing($listing);
        } else {
            parent::setEagerLoadedElements($handle, $elements, $plan);
        }
    }

    protected function attributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'rating':
                return $this->_buildRatingHtml(['centeralign', 'nowrap']);

            case 'platform':
                return $this->_buildPlatformHtml(['centeralign', 'nowrap']);

            case 'reviewable':
                return "{$this->reviewableName} ({$this->reviewableType})";

            case 'reviewContent':
                return LitEmoji::shortcodeToEntities((string) $this->reviewContent) ?: '(none)';

            case 'listing':
                if ($this->_listing === null && !$this->getListing()) {
                    return '(none)';
                }
                return Html::a($this->_listing->name, UrlHelper::cpUrl('stratus/listings?source=*&search=' . $this->stratusParentUuid));
        }

        return parent::attributeHtml($attribute);
    }

    /**
     * @inheritDoc
     */
    protected function metadata(): array
    {
        $formatter = Craft::$app->getFormatter();

        return [
            Craft::t('stratus', 'Stratus UUID') => function() {
                return Html::tag('div', $this->stratusUuid, [
                    'title' => $this->stratusUuid,
                    'aria' => [
                        'label' => $this->stratusUuid,
                    ],
                    'class' => 'stratus-ellipse'
                ]);
            },
            Craft::t('stratus', 'Stratus Parent UUID') => function() {
                return Html::tag('div', $this->stratusParentUuid, [
                    'title' => $this->stratusParentUuid,
                    'aria' => [
                        'label' => $this->stratusParentUuid,
                    ],
                    'class' => 'stratus-ellipse'
                ]);
            },
            Craft::t('stratus', 'Platform Name') => $this->platformName,
            Craft::t('stratus', 'Reviewable Type') => Craft::t('stratus', $this->reviewableType),
            Craft::t('stratus', 'Reviewable Name') => $this->reviewableName,
            Craft::t('stratus', 'Date Published') => function() use ($formatter) {
                return Html::tag('div', $formatter->asDate($this->platformPublishedDate), [
                    'title' => $formatter->asDateTime($this->platformPublishedDate),
                    'aria' => [
                        'label' => $formatter->asDateTime($this->platformPublishedDate),
                    ],
                ]);
            },
        ];
    }

    public function afterSave(bool $isNew): void
    {
        if ($isNew) {
            \Craft::$app->db->createCommand()
                ->insert('{{%stratus_reviews}}', [
                    'id' => $this->id,
                    'platform' => $this->platform,
                    'platformName' => $this->platformName,
                    'rating' => $this->rating,
                    'recommends' => $this->recommends,
                    'reviewContent' => $this->reviewContent,
                    'author' => $this->author,
                    'platformPublishedDate' => Db::prepareValueForDb($this->platformPublishedDate),
                    'reviewableType' => $this->reviewableType,
                    'reviewableName' => $this->reviewableName,
                    'stratusUuid' => $this->stratusUuid,
                    'stratusParentUuid' => $this->stratusParentUuid,
                ])
                ->execute();
        }

        parent::afterSave($isNew);
    }

    public function afterDelete(): void
    {
        \Craft::$app->db->createCommand()
            ->softDelete('{{%stratus_reviews}}', [
                'id' => $this->id,
            ])
            ->execute();

        parent::afterDelete();
    }

    /**
     * @return StratusListingElement|null
     */
    public function getListing()
    {
        if ($this->_listing === null) {
            if (!$this->stratusParentUuid) {
                return null;
            }

            $this->_listing = Stratus::getInstance()->stratus->getListingByUuid($this->stratusParentUuid) ?? false;
        }

        return $this->_listing ?: null;
    }

    /**
     * @param StratusListingElement|null $listing
     */
    public function setListing(StratusListingElement $listing = null)
    {
        $this->_listing = $listing;
        $this->stratusParentUuid = $listing->stratusUuid ?? null;
    }

    public function getIcons(): array
    {
        return [
            'platform' => $this->_buildPlatformHtml(),
            'rating' => $this->_buildRatingHtml(),
        ];
    }

    /**
     * Helper function for creating readonly fields for editor HTML
     *
     * @param string $label
     * @param string $html
     * @return string generated HTML
     */
    protected function _wrapFieldHtml($label, $html)
    {
        return Html::tag('div',
            Html::tag('div', Html::tag('label', $label), ['class' => 'heading']) .
            Html::tag('div', $html, ['class' => 'input']),
        ['class' => 'field']);
    }

    /**
     * Helper function to generate SVG based ratings HTML
     *
     * @param array $extraCssClass
     * @return string generated HTML
     */
    protected function _buildRatingHtml($extraCssClass = [])
    {

        $svgElements = [];

        if ($this->rating) {
            $svgElements = array_pad($svgElements, $this->rating, Cp::iconSvg('@clickrain/stratus/assetbundles/stratus/dist/img/star_black_24dp.svg', 'rating'));
            $svgElements = array_pad($svgElements, 5, Cp::iconSvg('@clickrain/stratus/assetbundles/stratus/dist/img/star_outline_black_24dp.svg', 'rating'));
        }

        if ($this->recommends !== null) {
            $svgElements[] = $this->recommends
                ? Html::tag('span', Cp::iconSvg('@clickrain/stratus/assetbundles/stratus/dist/img/thumb_up_black_24dp.svg', 'rating'), [
                        'class' => 'stratus-bg-facebook-approve'
                    ])
                : Html::tag('span', Cp::iconSvg('@clickrain/stratus/assetbundles/stratus/dist/img/thumb_down_black_24dp.svg', 'rating'), [
                        'class' => 'stratus-bg-facebook-disapprove'
                    ]);
        }

        $svgElements = array_map(function($svgElement) {
            return Html::tag('span', $svgElement, [
                'class' => ['stratus-icon', $this->rating ? 'stratus-bg-yellow' : null],
            ]);
        }, $svgElements);

        return Html::tag('span',
            implode('', $svgElements),
            ['class' => array_merge(['stratus-icon-wrap'], $extraCssClass)]
        );
    }

    /**
     * Helper function to generate SVG based platform HTML
     *
     * @param array $centerAlign
     * @return string generated HTML
     */
    protected function _buildPlatformHtml($extraCssClass = [])
    {
        return Html::tag('span',
            Html::tag('span', Cp::iconSvg("@clickrain/stratus/assetbundles/stratus/dist/img/{$this->platform}-icon.svg", 'rating'), [
                'class' => ['stratus-icon'],
            ]),
            ['class' => array_merge(['stratus-icon-wrap'], $extraCssClass)]
        );
    }

    public function getDetails(): array
    {
        return [
            'Review Details' => [
                'Listing' => $this->getListing() ? Html::a($this->getListing()->name, UrlHelper::cpUrl('stratus/listings?source=*&search=' . $this->stratusParentUuid)) : '(none)',
                'Platform' => Html::tag('div', $this->_buildPlatformHtml() . ' (' . $this->platformName . ')', ['class' => 'flex']),
                'Rating' => Html::tag('div', $this->_buildRatingHtml() . ' (' . ($this->recommends !== null ? ($this->recommends ? 'recommended' : 'not recommended') : '') . ($this->recommends === null ? $this->rating . ' star rating' : '') . ')', ['class' => 'flex']),
                'Author' => $this->author,
                'reviewContent' => $this->reviewContent,
                'Date Published' => Craft::$app->getFormatter()->asDate($this->platformPublishedDate),
            ],
            'System Details' => [
                'Craft ID' => $this->id,
                'Craft UID' => $this->uid,
                'Stratus UUID' => $this->stratusUuid,
                'Stratus Parent UUID' => $this->stratusParentUuid,
                'Reviewable Type' => Craft::t('stratus', $this->reviewableType),
                'Reviewable Name' => $this->reviewableName,
            ],
        ];
    }

    public function getContent(): string
    {
        return $this->reviewContent;
    }
}
