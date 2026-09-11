<?php
namespace clickrain\stratus\services;

use clickrain\stratus\elements\db\StratusListingQuery;
use Craft;
use craft\db\Query;
use craft\helpers\DateTimeHelper;
use craft\helpers\Queue;
use craft\helpers\Diff;
use clickrain\stratus\elements\db\StratusReviewQuery;
use clickrain\stratus\elements\StratusListingElement;
use clickrain\stratus\elements\StratusReviewElement;
use clickrain\stratus\events\SyncEvent;
use clickrain\stratus\jobs\ImportListingsTask;
use clickrain\stratus\jobs\ImportReviewsTask;
use clickrain\stratus\models\Settings;
use clickrain\stratus\Stratus;
use DateTime;
use Generator;
use LitEmoji\LitEmoji;
use yii\base\Component;
use yii\base\Event;
use yii\caching\TagDependency;
use yii\helpers\ArrayHelper;

class StratusService extends Component
{
    const REVIEWS_PULLED_CACHE_KEY = 'stratus-reviews-pulled-at-key';
    const REVIEWS_CACHE_TAG = 'stratus-reviews-pulled-at';
    const LISTINGS_PULLED_CACHE_KEY = 'stratus-listings-pulled-at-key';
    const LISTINGS_CACHE_TAG = 'stratus-listings-pulled-at';

    /**
     * @event Event The event that is triggered after the sync
     */
    public const EVENT_AFTER_SYNC = 'afterSync';

    /**
     * @event Event The event that is triggered before the sync
     */
    public const EVENT_BEFORE_SYNC = 'beforeSync';

    /**
     * Import the reviews
     *
     * @param bool $full  ignore the last pulled timestamp and pull all
     * reviews. WARNING: this is distructive.  It will delete existing reviews
     * before importing the new ones.
     * @return bool
     */
    public function importReviews(bool $fresh = true)
    {
        $cache = Craft::$app->getCache();

        // if we're doing a fresh pull, we need to invalidate our
        // timestamp.
        if ($fresh) {
            TagDependency::invalidate($cache, self::REVIEWS_CACHE_TAG);
        }

        // get the current timestamp.  If fresh, it was just reset to
        // be null.
        $after = $this->getReviewsLastPulledAt();

        // immediately set the pulled at timestamp here so we don't
        // miss any reviews that are added to the system while we're
        // processing the job being added to the queue.
        $cache->set(
            self::REVIEWS_PULLED_CACHE_KEY,
            DateTimeHelper::currentUTCDateTime(),
            null,
            new TagDependency([
                'tags' => self::REVIEWS_CACHE_TAG,
            ])
        );

        // queue the job with the after modifier.  $after will be
        // null if $fresh was true
        Queue::push(new ImportReviewsTask([
            'after' => $after,
        ]));

        return true;
    }
    /**
     * Import the listings
     *
     * @param bool $full  ignore the last pulled timestamp and pull all
     * listings. WARNING: this is distructive.  It will delete existing listings
     * before importing the new ones.
     * @return bool
     */
    public function importListings(bool $fresh = true)
    {
        $cache = Craft::$app->getCache();

        // if we're doing a fresh pull, we need to invalidate our
        // timestamp.
        if ($fresh) {
            TagDependency::invalidate($cache, self::LISTINGS_CACHE_TAG);
        }

        // get the current timestamp.  If fresh, it was just reset to
        // be null.
        $after = $this->getListingsLastPulledAt();

        // immediately set the pulled at timestamp here so we don't
        // miss any listings that are added to the system while we're
        // processing the job being added to the queue.
        $cache->set(
            self::LISTINGS_PULLED_CACHE_KEY,
            DateTimeHelper::currentUTCDateTime(),
            null,
            new TagDependency([
                'tags' => self::LISTINGS_CACHE_TAG,
            ])
        );

        // queue the job with the after modifier.  $after will be
        // null if $fresh was true
        Queue::push(new ImportListingsTask());

        return true;
    }

    /**
     * Return the last pulled at timestamp
     *
     * @return mixed
     */
    public function getReviewsLastPulledAt(): mixed
    {
        return Craft::$app->getCache()->get(self::REVIEWS_PULLED_CACHE_KEY) ?: null;
    }

    /**
     * Return the total count of reviews
     *
     * @return int
     */
    public function getTotalReviewCount()
    {
        return (new StratusReviewQuery(StratusReviewElement::class))->count();
    }

    /**
     * Return the last pulled at timestamp
     *
     * @return mixed
     */
    public function getListingsLastPulledAt(): mixed
    {
        return Craft::$app->getCache()->get(self::LISTINGS_PULLED_CACHE_KEY) ?: null;
    }

    /**
     * Return the total count of listings
     *
     * @return int
     */
    public function getTotalListingCount()
    {
        return (new StratusListingQuery(StratusListingElement::class))->count();
    }

    /**
     * Return the last pulled at timestamp
     *
     * @return void
     */
    public function getListingByUuid($uuid)
    {
        $entry = new StratusListingElement();
        return $entry->find()->where(['stratusUuid' => $uuid])->one();
    }

    public function getReviews($criteria = null): StratusReviewQuery
    {
        $query = StratusReviewElement::find();
        if ($criteria) {
            Craft::configure($query, $criteria);
        }
        return $query;
    }

    public function getListings($criteria = null): StratusListingQuery
    {
        $query = StratusListingElement::find();
        if ($criteria) {
            Craft::configure($query, $criteria);
        }
        return $query;
    }

    /**
     * Helper function to translate plarform identifiers to nice
     * names
     *
     * @param mixed $platform
     * @return string
     */
    public function getPlatformName($platform): string
    {
        $platforms = $this->getPlatforms();
        return $platforms[$platform] ?? 'Unknown Platform';
    }

    public function getPlatforms(): array
    {
        return [
            'google' => 'Google',
            'facebook' => 'Facebook',
            'healthgrades' => 'Healthgrades',
            'google_play_store' => 'Google Play Store',
            'apple_app_store' => 'Apple App Store',
            'yelp' => 'Yelp',
            'tripadvisor' => 'TripAdvisor',
            'bbb' => 'BBB',
            'indeed' => 'Indeed',
            'glassdoor' => 'Glassdoor',
            'yellow_pages' => 'Yellow Pages',
            'zocdoc' => 'Zocdoc',
            'vitals' => 'Vitals',
            'realself' => 'RealSelf',
            'ratemds' => 'RateMDs',
            'webmd' => 'WebMD',
            'zillow' => 'Zillow',
        ];
    }

    public function getPlatformIdentifiers(): array
    {
        return array_keys($this->getPlatforms());
    }

    /**
     * Move any literal secrets out of the settings and into .env, replacing
     * them with a reference such as `$STRATUS_API_KEY`.
     *
     * Plugin settings are written to project config, which is version
     * controlled and synced between environments, so a literal API key or
     * signing secret would be committed and shared. Values that are already a
     * reference, or empty, are left alone.
     *
     * Writing to .env is not possible everywhere — there may be no .env file,
     * or the filesystem may be read only — so a failure leaves the value as it
     * was and is reported back to the caller rather than blocking the save.
     *
     * @param Settings $settings  modified in place
     * @return string[] attribute names that could not be moved
     */
    public function moveSecretsToEnv(Settings $settings): array
    {
        /** @var \craft\services\Config */
        $configService = Craft::$app->getConfig();
        $failed = [];

        foreach (Settings::SECRET_ATTRIBUTES as $attribute => $envVar) {
            $value = (string)$settings->$attribute;

            // Empty, or already a reference such as $STRATUS_API_KEY
            if ($value === '' || str_starts_with($value, '$')) {
                continue;
            }

            try {
                $configService->setDotEnvVar($envVar, $value);
                $settings->$attribute = '$' . $envVar;
            } catch (\Throwable $e) {
                Craft::warning(sprintf(
                    'Could not move %s into %s, so it will be stored in project config as plain text: %s',
                    $attribute,
                    $envVar,
                    $e->getMessage()
                ), __METHOD__);

                $failed[] = $attribute;
            }
        }

        return $failed;
    }

    /**
     * Find the element already holding a stratusUuid.
     *
     * The element queries can only return rows whose element is intact, but the
     * unique index on stratusUuid covers every row in the table. When those two
     * disagree — the row survived but its element did not, or the id now belongs
     * to another element type — the caller would build a new element and the
     * insert would collide with the index, failing the whole import.
     *
     * Looking the id up in the table first keeps the check on the same source of
     * truth as the constraint. Rows whose element can no longer be resolved are
     * dropped so the record can be imported cleanly on this pass.
     *
     * @param string $table         the element's table, e.g. '{{%stratus_reviews}}'
     * @param string $elementClass  the element class the table's ids should point at
     * @param string $uuid          the stratusUuid to look for
     * @return int|null the id to reuse, or null if there is nothing usable
     */
    private function _findIdByUuid(string $table, string $elementClass, string $uuid): ?int
    {
        $id = (new Query())
            ->select(['id'])
            ->from([$table])
            ->where(['stratusUuid' => $uuid])
            ->scalar();

        if (!$id) {
            return null;
        }

        $type = (new Query())
            ->select(['type'])
            ->from(['{{%elements}}'])
            ->where(['id' => $id])
            ->scalar();

        if ($type === $elementClass) {
            return (int)$id;
        }

        Craft::warning(sprintf(
            'Purging orphaned %s row #%d (uuid %s): expected element type %s, found %s.',
            $table,
            $id,
            $uuid,
            $elementClass,
            $type === false || $type === null ? 'no element' : $type
        ), __METHOD__);

        Craft::$app->getDb()->createCommand()->delete($table, ['id' => $id])->execute();

        return null;
    }

    public function syncListings(array $listings): Generator
    {
        /** @var \craft\services\Elements */
        $elementsService = Craft::$app->getElements();
        /** @var \craft\services\Search */
        $searchService = Craft::$app->getSearch();

        Event::trigger(static::class, self::EVENT_BEFORE_SYNC, new SyncEvent([
            'type' => 'listing',
        ]));

        foreach ($listings as $listing) {
            /** @var StratusListingElement */
            $entry = new StratusListingElement();
            $existingEntry = null;
            if ($existingId = $this->_findIdByUuid(
                '{{%stratus_listings}}',
                StratusListingElement::class,
                $listing['uuid']
            )) {
                /** @var StratusListingElement|null $existingEntry */
                $existingEntry = StratusListingElement::find()
                    ->id($existingId)
                    ->status(null)
                    ->trashed(null)
                    ->one();

                if ($existingEntry) {
                    $entry = $existingEntry;
                } else {
                    // The element is intact but filtered out of the query, so
                    // update it in place rather than inserting a duplicate.
                    $entry->id = $existingId;
                }
            }
            $entry->name = $listing['name'];
            $entry->type = $listing['type'];
            $entry->address = $listing['address'];
            $entry->address2 = $listing['address2'];
            $entry->city = $listing['city'];
            $entry->state = $listing['state'];
            $entry->zip = $listing['zip'];
            $entry->phone = $listing['phone'];
            $entry->timezone = $listing['timezone'];
            $entry->hours = $listing['hours'];
            $entry->holidayHours = $listing['holiday_hours'];
            $entry->reviewables = $listing['reviewables'] ?: null;
            $entry->stratusUuid = $listing['uuid'];

            if ($listing['deleted_at'] !== null && $existingEntry) {
                $elementsService->deleteElement($entry);
            } else {
                $elementsService->saveElement($entry, updateSearchIndex: false);
                if ($entry->trashed) {
                    $elementsService->restoreElement($entry);
                }
                $searchService->indexElementAttributes($entry);
            }

            if (!empty($entry->getFirstErrors())) {
                Craft::warning(Craft::t('stratus', 'Could not create record for imported listing:') . PHP_EOL .
                    json_encode($listing) . PHP_EOL .
                    '(' . implode(';', $entry->getFirstErrors()) . ')',
                'stratus');
            }

            yield $entry;
        }

        Event::trigger(static::class, self::EVENT_AFTER_SYNC, new SyncEvent([
            'type' => 'listing',
        ]));
    }

    public function syncReviews(array $reviews): Generator
    {
        /** @var \craft\services\Elements */
        $elementsService = Craft::$app->getElements();
        /** @var \craft\services\Search */
        $searchService = Craft::$app->getSearch();

        Event::trigger(static::class, self::EVENT_BEFORE_SYNC, new SyncEvent([
            'type' => 'review',
        ]));

        foreach ($reviews as $review) {
            /** @var StratusReviewElement */
            $entry = new StratusReviewElement();
            $existingEntry = null;
            if ($existingId = $this->_findIdByUuid(
                '{{%stratus_reviews}}',
                StratusReviewElement::class,
                $review['uuid']
            )) {
                /** @var StratusReviewElement|null $existingEntry */
                $existingEntry = StratusReviewElement::find()
                    ->id($existingId)
                    ->status(null)
                    ->trashed(null)
                    ->one();

                if ($existingEntry) {
                    $entry = $existingEntry;
                } else {
                    // The element is intact but filtered out of the query — a
                    // review whose parent listing hasn't imported yet will not
                    // resolve. Update in place rather than inserting a duplicate.
                    $entry->id = $existingId;
                }
            }
            $entry->platform = $review['platform'];
            $entry->platformName = $this->getPlatformName($review['platform']);
            $entry->rating = $review['rating'];
            $entry->recommends = $review['recommendation'] !== null
                ? $review['recommendation'] === 'positive'
                : null;
            $entry->author = $review['author'];
            $entry->platformPublishedDate = new DateTime($review['platform_published_date']);
            $entry->reviewContent = LitEmoji::unicodeToShortcode($review['content'] ?: '');
            $entry->reviewableType = $review['reviewable_type'];
            $entry->reviewableName = $review['reviewable_name'];
            $entry->stratusUuid = $review['uuid'];
            $entry->stratusParentUuid = $review['parent_uuid'];

            if ($review['deleted_at'] !== null && $existingEntry) {
                $elementsService->deleteElement($entry);
            } else {
                $elementsService->saveElement($entry, updateSearchIndex: false);
                if ($entry->trashed) {
                    $elementsService->restoreElement($entry);
                }
                $searchService->indexElementAttributes($entry);
            }

            if (!empty($entry->getFirstErrors())) {
                Craft::warning(Craft::t('stratus', 'Could not create record for imported review:') . PHP_EOL .
                    json_encode($review) . PHP_EOL .
                    '(' . implode(';', $entry->getFirstErrors()) . ')',
                'stratus');
            }

            yield $entry;
        }

        Event::trigger(static::class, self::EVENT_AFTER_SYNC, new SyncEvent([
            'type' => 'review',
        ]));
    }


    public static function onBeforeSaveSettingsListener(Event $event)
    {
        /** @var \craft\services\Plugins */
        $pluginsService = Craft::$app->getPlugins();
        /** @var \clickrain\stratus\models\Settings */
        $settings = $event->sender->getSettings();

        $oldSettings = ArrayHelper::getValue(
            $pluginsService->getStoredPluginInfo('stratus'),
            'settings',
            []
        );

        // if there are changes, we need to pull the new data
        if (Diff::compare($settings->toArray(), $oldSettings) === false) {
            Queue::push(job: new ImportListingsTask());
            Queue::push(job: new ImportReviewsTask());
        }
    }
}