<?php

namespace clickrain\stratus\tests\integration;

use Codeception\Test\Unit;
use Craft;
use clickrain\stratus\Stratus;
use clickrain\stratus\tests\_support\Payloads;
use craft\models\GqlSchema;

/**
 * GraphQL is the API surface most exposed to Craft's own churn: type
 * generators, interface registration, schema components and resolvers have all
 * changed shape across releases. These tests build a real schema and run real
 * queries through it, so a break shows up as a failing query rather than as a
 * support ticket from a headless front end.
 */
class GraphqlTest extends Unit
{
    protected \IntegrationTester $tester;

    protected function _before(): void
    {
        // Craft memoises GraphQL types in a static registry and caches the
        // built schema on the service. Neither is reset between tests, so a
        // schema built by one test would otherwise be handed to the next.
        \craft\gql\GqlEntityRegistry::flush();
        Craft::$app->getGql()->flushCaches();

        $service = Stratus::$plugin->stratus;

        iterator_to_array($service->syncListings([Payloads::listing()]));
        iterator_to_array($service->syncReviews([
            Payloads::review(),
            Payloads::review([
                'uuid' => 'review-0000-0000-0000-000000000002',
                'platform' => 'yelp',
                'rating' => 2,
                'author' => 'C. Reviewer',
            ]),
        ]));

        $this->useFullAccessSchema();
    }

    private function useFullAccessSchema(): void
    {
        $schema = new GqlSchema([
            'id' => 1,
            'name' => 'Stratus test schema',
            'scope' => [
                'stratus.reviews:read',
                'stratus.listings:read',
            ],
        ]);

        Craft::$app->getGql()->setActiveSchema($schema);
    }

    private function rebuildSchema(): \GraphQL\Type\Schema
    {
        return Craft::$app->getGql()->getSchemaDef(null, true);
    }

    private function execute(string $query): array
    {
        $result = Craft::$app->getGql()->executeQuery(
            Craft::$app->getGql()->getActiveSchema(),
            $query,
        );

        $this->assertArrayNotHasKey(
            'errors',
            $result,
            'GraphQL returned errors: ' . json_encode($result['errors'] ?? [], JSON_PRETTY_PRINT),
        );

        return $result['data'];
    }

    public function testSchemaExposesThePluginsQueries(): void
    {
        $queries = $this->rebuildSchema()->getQueryType()->getFields();

        foreach (['stratusReviews', 'stratusReview', 'stratusListings', 'stratusListing'] as $name) {
            $this->assertArrayHasKey($name, $queries, "The $name query is missing from the schema.");
        }
    }

    public function testReviewsCanBeQueried(): void
    {
        $data = $this->execute('{
            stratusReviews {
                stratusUuid
                platform
                rating
                author
            }
        }');

        $this->assertCount(2, $data['stratusReviews']);
        $this->assertSame('google', $data['stratusReviews'][0]['platform']);
    }

    public function testReviewQueryAcceptsThePluginsArguments(): void
    {
        $data = $this->execute('{
            stratusReviews(platform: [yelp]) {
                stratusUuid
                rating
            }
        }');

        $this->assertCount(1, $data['stratusReviews']);
        $this->assertSame('review-0000-0000-0000-000000000002', $data['stratusReviews'][0]['stratusUuid']);
    }

    public function testSingleReviewCanBeQueried(): void
    {
        $data = $this->execute('{
            stratusReview(uuid: "review-0000-0000-0000-000000000001") {
                author
            }
        }');

        $this->assertSame('A. Reviewer', $data['stratusReview']['author']);
    }

    public function testListingsCanBeQueriedWithTheirReviews(): void
    {
        $data = $this->execute('{
            stratusListings {
                name
                city
                reviews {
                    stratusUuid
                }
            }
        }');

        $this->assertCount(1, $data['stratusListings']);
        $this->assertSame('Click Rain', $data['stratusListings'][0]['name']);
        $this->assertCount(2, $data['stratusListings'][0]['reviews']);
    }

    /**
     * A schema without the plugin's scopes must not expose the queries at all.
     */
    public function testQueriesAreHiddenFromASchemaWithoutTheScopes(): void
    {
        Craft::$app->getGql()->setActiveSchema(new GqlSchema([
            'id' => 2,
            'name' => 'No stratus access',
            'scope' => [],
        ]));

        $queries = $this->rebuildSchema()->getQueryType()->getFields();

        $this->assertArrayNotHasKey('stratusReviews', $queries);
        $this->assertArrayNotHasKey('stratusListings', $queries);
    }

    /**
     * Pins the names the plugin's types get in the published schema. Renaming a
     * type breaks every consumer's queries, so it should never happen by
     * accident, which is exactly what happened when Craft 5 dropped
     * gqlTypeNameByContext(): the types silently became StratusReviewElement
     * and StratusListingElement.
     */
    public function testConcreteTypeNamesAreStable(): void
    {
        $types = $this->rebuildSchema()->getTypeMap();

        foreach ([
            'StratusReviewInterface',
            'StratusListingInterface',
            'StratusReview',
            'StratusListing',
        ] as $name) {
            $this->assertArrayHasKey($name, $types, "The GraphQL type $name is no longer in the schema.");
        }
    }
}
