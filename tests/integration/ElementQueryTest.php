<?php

namespace clickrain\stratus\tests\integration;

use Codeception\Test\Unit;
use clickrain\stratus\Stratus;
use clickrain\stratus\elements\StratusListingElement;
use clickrain\stratus\elements\StratusReviewElement;
use clickrain\stratus\tests\_support\Payloads;

/**
 * The custom query params build SQL by hand against Craft's query internals
 * (joinElementTable, subQuery, Db::parseParam). Craft reworks element queries
 * regularly, and a change there produces either invalid SQL or quietly wrong
 * results. Each param is executed here so both show up.
 */
class ElementQueryTest extends Unit
{
    protected \IntegrationTester $tester;

    protected function _before(): void
    {
        $service = Stratus::$plugin->stratus;

        iterator_to_array($service->syncListings([
            Payloads::listing(),
            Payloads::listing([
                'uuid' => 'listing-0000-0000-0000-000000000002',
                'name' => 'Second Location',
                'type' => 'practitioner',
                'city' => 'Brandon',
            ]),
        ]));

        iterator_to_array($service->syncReviews([
            Payloads::review(),
            Payloads::review([
                'uuid' => 'review-0000-0000-0000-000000000002',
                'platform' => 'facebook',
                'rating' => null,
                'recommendation' => 'positive',
                'author' => 'B. Reviewer',
                'platform_published_date' => '2026-03-20 09:00:00',
                'reviewable_type' => 'practitioner',
                'parent_uuid' => 'listing-0000-0000-0000-000000000002',
            ]),
            Payloads::review([
                'uuid' => 'review-0000-0000-0000-000000000003',
                'platform' => 'yelp',
                'rating' => 2,
                'author' => 'C. Reviewer',
                'platform_published_date' => '2026-05-01 09:00:00',
            ]),
        ]));
    }

    public function testReviewsCanBeFilteredByPlatform(): void
    {
        $this->assertSame(
            ['review-0000-0000-0000-000000000002'],
            $this->uuids(StratusReviewElement::find()->platform('facebook')),
        );
    }

    public function testReviewsCanBeFilteredBySeveralPlatforms(): void
    {
        $this->assertCount(2, StratusReviewElement::find()->platforms(['google', 'yelp'])->all());
    }

    public function testReviewsCanBeFilteredByRating(): void
    {
        $this->assertSame(
            ['review-0000-0000-0000-000000000001'],
            $this->uuids(StratusReviewElement::find()->rating(5)),
        );
    }

    public function testReviewsCanBeFilteredByRecommendation(): void
    {
        $this->assertSame(
            ['review-0000-0000-0000-000000000002'],
            $this->uuids(StratusReviewElement::find()->recommends(true)),
        );
    }

    /**
     * rating and recommends combine with OR, because a platform reports one or
     * the other but never both.
     */
    public function testRatingAndRecommendsCombineWithOr(): void
    {
        $this->assertCount(
            2,
            StratusReviewElement::find()->rating(5)->recommends(true)->all(),
        );
    }

    public function testReviewsCanBeFilteredByReviewableType(): void
    {
        $this->assertSame(
            ['review-0000-0000-0000-000000000002'],
            $this->uuids(StratusReviewElement::find()->type('practitioner')),
        );
    }

    public function testReviewsCanBeFilteredByListingUuid(): void
    {
        $this->assertCount(
            2,
            StratusReviewElement::find()->listing('listing-0000-0000-0000-000000000001')->all(),
        );
    }

    public function testReviewsCanBeFilteredByListingId(): void
    {
        $listing = StratusListingElement::find()->uuid('listing-0000-0000-0000-000000000002')->one();

        $this->assertSame(
            ['review-0000-0000-0000-000000000002'],
            $this->uuids(StratusReviewElement::find()->listingId($listing->id)),
        );
    }

    public function testReviewsCanBeFilteredByPublishedDate(): void
    {
        $this->assertSame(
            ['review-0000-0000-0000-000000000003'],
            $this->uuids(StratusReviewElement::find()->datePublished('>= 2026-04-01')),
        );
    }

    public function testReviewsCanBeSortedByEveryDeclaredSortOption(): void
    {
        foreach ($this->sortAttributes(StratusReviewElement::class) as $attribute) {
            $this->assertNotEmpty(
                StratusReviewElement::find()->orderBy("$attribute asc")->all(),
                "Sorting reviews by '$attribute' returned nothing, so the option is broken.",
            );
        }
    }

    public function testListingsCanBeFilteredByNameTypeAndUuid(): void
    {
        $this->assertSame(
            ['listing-0000-0000-0000-000000000002'],
            $this->uuids(StratusListingElement::find()->name('Second Location')),
        );
        $this->assertSame(
            ['listing-0000-0000-0000-000000000002'],
            $this->uuids(StratusListingElement::find()->type('practitioner')),
        );
        $this->assertSame(
            ['listing-0000-0000-0000-000000000001'],
            $this->uuids(StratusListingElement::find()->uuid('listing-0000-0000-0000-000000000001')),
        );
    }

    public function testListingsCanBeSortedByEveryDeclaredSortOption(): void
    {
        foreach ($this->sortAttributes(StratusListingElement::class) as $attribute) {
            $this->assertNotEmpty(
                StratusListingElement::find()->orderBy("$attribute asc")->all(),
                "Sorting listings by '$attribute' returned nothing, so the option is broken.",
            );
        }
    }

    /**
     * Craft asks elements for their index sources and renders a tab per source.
     * A source whose criteria no longer resolve leaves an empty or broken tab in
     * the control panel, which is invisible until someone opens it.
     */
    public function testEveryElementIndexSourceResolves(): void
    {
        foreach ([StratusReviewElement::class, StratusListingElement::class] as $elementType) {
            foreach ($elementType::sources('index') as $source) {
                if (($source['type'] ?? null) !== 'key') {
                    continue;
                }

                $query = $elementType::find();
                foreach ($source['criteria'] ?? [] as $param => $value) {
                    $query->$param($value);
                }

                $this->assertIsInt(
                    $query->count(),
                    "Source '{$source['key']}' on $elementType does not produce a runnable query.",
                );
            }
        }
    }

    /**
     * @return list<string>
     */
    private function sortAttributes(string $elementType): array
    {
        $attributes = [];

        foreach ($elementType::sortOptions() as $key => $option) {
            $attribute = is_array($option) ? ($option['orderBy'] ?? null) : $key;
            if (is_string($attribute)) {
                $attributes[] = $attribute;
            }
        }

        $this->assertNotEmpty($attributes, "$elementType declares no usable sort options.");

        return $attributes;
    }

    /**
     * @return list<string>
     */
    private function uuids(\craft\elements\db\ElementQuery $query): array
    {
        return array_map(
            static fn($element) => $element->stratusUuid,
            $query->orderBy('stratusUuid asc')->all(),
        );
    }
}
