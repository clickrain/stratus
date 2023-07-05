<?php

namespace clickrain\stratus\jobs;

use Craft;
use clickrain\stratus\Stratus;

use craft\queue\BaseJob;
use clickrain\stratus\elements\StratusReviewElement;
use clickrain\stratus\services\StratusService;
use DateTime;
use Exception;

/**
 * ImportReviews job
 *
 * Jobs are run in separate process via a Queue of pending jobs. This allows
 * you to spin lengthy processing off into a separate PHP process that does not
 * block the main process.
 *
 * You can use it like this:
 *
 * use clickrain\stratus\jobs\ImportReviewsTask as ImportReviewsTaskJob;
 *
 * $queue = Craft::$app->getQueue();
 * $jobId = $queue->push(new ImportReviewsTaskJob([
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
class ImportReviewsTask extends BaseJob
{
    use traits\MakesApiRequests;

    /**
     * @var array
     */
    protected $_meta;

    /**
     * @var \clickrain\stratus\services\StratusService
     */
    protected $_service;

    /**
     * @var \clickrain\stratus\models\Settings
     */
    protected $_settings;

    /**
     * @var array|null reviews to import
     */
    public ?array $reviews = null;

    // Public Properties
    // =========================================================================

    /**
     * @var \DateTime|null The utc date that should be passed to the
     * stratus API to only get recent reviews
     */
    public $after;

    /**
     * @var bool decide if we should do a fresh pull or just find the
     * latest reviews
     */
    public $fresh;

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
            if ($this->fresh) {
                $this->setProgress($queue, 0, label: 'Finding old data');
                $this->_deleteReviewEntries(function($total, $index) use ($queue) {
                    $this->setProgress($queue, $index / $total, label: 'Cleaning up old records');
                });
            }

            $this->setProgress($queue, 0, label: 'Fetching records');
            $reviews = [];
            if ($this->reviews === null) {
                foreach ($this->_fetchReviews() as $index => $review) {
                    $reviews[] = $review['review'];
                    $this->setProgress($queue, $index / $review['total'], label: 'Fetching records');
                }
            } else {
                $reviews = $this->reviews;
            }
            $this->setProgress($queue, 1, label: 'Fetching records');

            // find which ones we should remove
            $elementsToRemove = (new StratusReviewElement())
                ->find()
                ->where(
                    ['not in', 'stratusUuid', array_column($reviews, 'uuid')]
                )->all();


            // soft delete them
            $total = count($elementsToRemove);
            foreach ($elementsToRemove as $index => $element) {
                $this->setProgress(
                    $queue,
                    progress: $index / $total,
                    label: 'Removing old reviews'
                );
                $elementsService->deleteElement($element);
            }

            // sync valid reviews to elements
            $total = count($reviews);
            foreach ($this->_getService()->syncReviews($reviews) as $index => $listing) {
                $this->setProgress(
                    $queue,
                    progress: $index / $total,
                    label: 'Syncing reviews'
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
        return Craft::t('stratus', 'Importing reviews');
    }

    /**
     * Perform the external fetch for reviews.  This makes a request to the
     * Stratus API via the URL in the configuration.
     *
     * @param \DateTime|null $sinceDatetime  Only return reviews that are newer
     * than the provided timestamp.  All reviews are fetched if null.
     * @return \Generator
     */
    protected function _fetchReviews(): \Generator
    {
        $path = '/api/public/reviews?';
        $paginatedPath = $path . http_build_query([
            'page' => 1,
            'after' => $this->after ? $this->after->getTimestamp() : null,
        ]);

        $response = null;

        do {
            $request = $this->makeRequest($paginatedPath);
            $body = $request->getBody();
            $response = json_decode($body, true);

            if ($response === null) {
                Craft::error('Could not decode response: ' . json_encode([
                    'path' => rtrim($this->_settings->getBaseUrl(), '/') . $paginatedPath,
                    'body' => $body,
                    'status' => $request->getStatusCode(),
                    'headers' => $request->getHeaders()
                ]));
                throw new Exception('Failed to parse response body');
            }

            $this->_meta = $response['meta'];

            foreach ($response['data'] as $review) {
                yield [
                    'total' => $this->_meta['total'],
                    'review' => $review
                ];
            }
        } while (
            $response['links']['next'] &&
            ($paginatedPath = $path . parse_url($response['links']['next'], PHP_URL_QUERY))
        );
    }

    protected function _deleteReviewEntries(callable $progressCallback)
    {
        /** @var \craft\services\Elements */
        $elementsService = Craft::$app->getElements();

        $elements = $elementsService->createElement(StratusReviewElement::class)
            ->find()
            ->trashed(null)
            ->all();
        $total = count($elements);

        foreach ($elements as $i => $element) {
            $elementsService->deleteElement($element, hardDelete: true);
            $progressCallback($total, $i);
        }

        return true;
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
