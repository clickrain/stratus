<?php

namespace clickrain\stratus\jobs;

use clickrain\stratus\elements\StratusListingElement;
use Craft;
use clickrain\stratus\Stratus;
use clickrain\stratus\services\StratusService;


use craft\queue\BaseJob;

/**
 * ImportReviews job
 *
 * Jobs are run in separate process via a Queue of pending jobs. This allows
 * you to spin lengthy processing off into a separate PHP process that does not
 * block the main process.
 *
 * You can use it like this:
 *
 * use clickrain\stratus\jobs\ImportListingsTask as ImportListingsTaskJob;
 *
 * $queue = Craft::$app->getQueue();
 * $jobId = $queue->push(new ImportListingsTaskJob([
 *     'description' => Craft::t('stratus', 'This overrides the default description'),
 *     'someAttribute' => 'someValue',
 * ]));
 *
 * The key/value pairs that you pass in to the job will set the public properties
 * for that object. Thus whatever you set 'someAttribute' to will cause the
 * public property $someAttribute to be set in the job.
 *
 * Passing in 'description' is optional, and only if you want to override the default
 * description.
 *
 * More info: https://github.com/yiisoft/yii2-queue
 *
 * @author    Joseph
 * @package   Stratus
 * @since     1.0.0
 */
class ImportListingsTask extends BaseJob
{
    use traits\MakesApiRequests;

    /**
     * @var \clickrain\stratus\services\StratusService
     */
    protected $_service;

    /**
     * @var \clickrain\stratus\models\Settings
     */
    protected $_settings;

    /**
     * @var array|null listings to import
     */
    public ?array $listings = null;

    // Public Properties
    // =========================================================================

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        $plugin = Stratus::getInstance();
        $this->_service = $plugin->stratus;
        $this->_settings = $plugin->getSettings();
    }

    /**
     * When the Queue is ready to run your job, it will call this method.
     * You don't need any steps or any other special logic handling, just do the
     * jobs that needs to be done here.
     *
     * More info: https://github.com/yiisoft/yii2-queue
     */
    public function execute($queue): void
    {
        /** @var \craft\services\Elements */
        $elementsService = Craft::$app->getElements();

        try {
            // get listings from Stratus
            $this->setProgress(
                $queue,
                progress: 0,
                label: 'Fetching listings'
            );
            $listings = $this->_fetchListings();

            // find which ones we should remove
            $elementsToRemove = (new StratusListingElement())
                ->find()
                ->where(
                    ['not in', 'stratusUuid', array_column($listings, 'uuid')]
                )->all();

            // soft delete them
            $total = count($elementsToRemove);
            foreach ($elementsToRemove as $index => $element) {
                $this->setProgress(
                    $queue,
                    progress: $index / $total,
                    label: 'Removing old listings'
                );
                $elementsService->deleteElement($element);
            }

            // sync valid listings to elements
            $total = count($listings);
            foreach ($this->_getService()->syncListings($listings) as $index => $listing) {
                $this->setProgress(
                    $queue,
                    progress: $index / $total,
                    label: 'Syncing listings'
                );
            }
        } catch (\Throwable $e) {
            // Don’t let an exception block the queue
            Craft::warning("Something went wrong: {$e->getMessage()}", __METHOD__);
        }
    }

    // Protected Methods
    // =========================================================================

    /**
     * Returns a default description for [[getDescription()]], if [[description]] isn’t set.
     *
     * @return string The default task description
     */
    protected function defaultDescription(): string
    {
        return Craft::t('stratus', 'Importing listings');
    }

    /**
     * Perform the external fetch for listings.  This makes a request to the
     * Stratus API via the URL in the configuration.
     *
     * @return array
     */
    protected function _fetchListings()
    {
        $response = $this->makeRequest(
            '/api/public/listings'
        );

        return json_decode($response->getBody(), true);
    }

    /**
     * Get the stratus service
     *
     * @return \clickrain\stratus\services\StratusService
     */
    protected function _getService(): StratusService
    {
        return Stratus::getInstance()->stratus;
    }
}
