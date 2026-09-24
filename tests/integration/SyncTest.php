<?php

namespace clickrain\stratus\tests\integration;

use Codeception\Test\Unit;
use Craft;
use clickrain\stratus\Stratus;
use clickrain\stratus\elements\StratusListingElement;
use clickrain\stratus\elements\StratusReviewElement;
use clickrain\stratus\tests\_support\Payloads;

/**
 * Importing is the whole job of this plugin, and it leans on the parts of Craft
 * that move most: saveElement(), deleteElement(), restoreElement() and the
 * search index. These tests drive the sync the way a real import does and check
 * what actually landed in the database.
 */
class SyncTest extends Unit
{
    protected \IntegrationTester $tester;

    private function service(): \clickrain\stratus\services\StratusService
    {
        return Stratus::$plugin->stratus;
    }

    /**
     * The sync methods are generators, so nothing happens until they are drawn.
     */
    private function sync(string $type, array $payloads): array
    {
        $method = $type === 'listing' ? 'syncListings' : 'syncReviews';

        return iterator_to_array($this->service()->$method($payloads));
    }

    public function testListingImportCreatesAnElement(): void
    {
        $this->sync('listing', [Payloads::listing()]);

        $listing = StratusListingElement::find()
            ->uuid('listing-0000-0000-0000-000000000001')
            ->one();

        $this->assertNotNull($listing, 'The listing was not saved.');
        $this->assertSame('Click Rain', $listing->name);
        $this->assertSame('Sioux Falls', $listing->city);
    }

    public function testReviewImportCreatesAnElementLinkedToItsListing(): void
    {
        $this->sync('listing', [Payloads::listing()]);
        $this->sync('review', [Payloads::review()]);

        $review = StratusReviewElement::find()
            ->uuid('review-0000-0000-0000-000000000001')
            ->one();

        $this->assertNotNull($review, 'The review was not saved.');
        $this->assertSame('google', $review->platform);
        $this->assertSame(5, (int)$review->rating);
        $this->assertSame('A. Reviewer', $review->author);
        $this->assertInstanceOf(StratusListingElement::class, $review->getListing());
    }

    /**
     * Re-importing is the common case: Stratus resends records the plugin has
     * already seen. Matching on uuid has to update in place, or every import
     * doubles the review count.
     */
    public function testReimportingUpdatesInPlaceRatherThanDuplicating(): void
    {
        $this->sync('listing', [Payloads::listing()]);
        $this->sync('review', [Payloads::review()]);
        $this->sync('review', [Payloads::review(['rating' => 3, 'author' => 'Renamed Reviewer'])]);

        $reviews = StratusReviewElement::find()
            ->uuid('review-0000-0000-0000-000000000001')
            ->all();

        $this->assertCount(1, $reviews, 'Re-importing the same uuid created a duplicate.');
        $this->assertSame(3, (int)$reviews[0]->rating);
        $this->assertSame('Renamed Reviewer', $reviews[0]->author);
    }

    public function testDeletedAtSoftDeletesAnExistingReview(): void
    {
        $this->sync('listing', [Payloads::listing()]);
        $this->sync('review', [Payloads::review()]);
        $this->sync('review', [Payloads::review(['deleted_at' => '2026-02-01 00:00:00'])]);

        $this->assertNull(
            StratusReviewElement::find()->uuid('review-0000-0000-0000-000000000001')->one(),
            'A deleted review is still returned by a normal query.',
        );
        $this->assertNotNull(
            StratusReviewElement::find()->uuid('review-0000-0000-0000-000000000001')->trashed()->one(),
            'The deleted review was hard deleted instead of trashed.',
        );
    }

    /**
     * Stratus can undelete a review. Craft will not resurrect a trashed element
     * on save alone, so the sync has to call restoreElement().
     */
    public function testReimportingATrashedReviewRestoresIt(): void
    {
        $this->sync('listing', [Payloads::listing()]);
        $this->sync('review', [Payloads::review()]);
        $this->sync('review', [Payloads::review(['deleted_at' => '2026-02-01 00:00:00'])]);
        $this->sync('review', [Payloads::review()]);

        $review = StratusReviewElement::find()
            ->uuid('review-0000-0000-0000-000000000001')
            ->one();

        $this->assertNotNull($review, 'A restored review is not queryable again.');
        $this->assertFalse($review->trashed);
    }

    public function testDeletedAtSoftDeletesAnExistingListing(): void
    {
        $this->sync('listing', [Payloads::listing()]);
        $this->sync('listing', [Payloads::listing(['deleted_at' => '2026-02-01 00:00:00'])]);

        $this->assertNull(
            StratusListingElement::find()->uuid('listing-0000-0000-0000-000000000001')->one(),
        );
    }

    /**
     * A review arriving before its listing cannot be resolved by the element
     * query, which inner joins the listings table. The sync falls back to
     * updating by id so the next import does not insert a second copy.
     */
    public function testReviewWithNoImportedListingDoesNotDuplicateOnReimport(): void
    {
        $orphan = Payloads::review([
            'uuid' => 'review-0000-0000-0000-000000000002',
            'parent_uuid' => 'listing-not-yet-imported',
        ]);

        $this->sync('review', [$orphan]);
        $this->sync('review', [$orphan]);

        $rows = (new \craft\db\Query())
            ->from('{{%stratus_reviews}}')
            ->where(['stratusUuid' => 'review-0000-0000-0000-000000000002'])
            ->count();

        $this->assertSame(1, (int)$rows, 'An orphaned review was inserted twice.');
    }

    public function testSyncFiresItsBeforeAndAfterEvents(): void
    {
        $fired = [];
        $service = \clickrain\stratus\services\StratusService::class;

        $before = function() use (&$fired) {
            $fired[] = 'before';
        };
        $after = function() use (&$fired) {
            $fired[] = 'after';
        };

        \yii\base\Event::on($service, $service::EVENT_BEFORE_SYNC, $before);
        \yii\base\Event::on($service, $service::EVENT_AFTER_SYNC, $after);

        try {
            $this->sync('listing', [Payloads::listing()]);
        } finally {
            \yii\base\Event::off($service, $service::EVENT_BEFORE_SYNC, $before);
            \yii\base\Event::off($service, $service::EVENT_AFTER_SYNC, $after);
        }

        $this->assertSame(['before', 'after'], $fired);
    }
}
