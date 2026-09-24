<?php

namespace clickrain\stratus\tests\conformance;

use Codeception\Test\Unit;
use clickrain\stratus\tests\_support\PluginSource;
use ReflectionClass;
use ReflectionMethod;

/**
 * Catches the one kind of Craft breakage static analysis cannot see.
 *
 * When Craft drops a hook the plugin overrides, the override becomes an
 * ordinary method that nothing calls. PHP allows it, PHPStan allows it at every
 * level, and the plugin keeps installing and running. The behaviour just
 * quietly stops happening. Craft 5 removed hasContent(), getContentColumnType()
 * and gqlTypeNameByContext() this way and the nightly static analysis stayed
 * green for months.
 *
 * So: every public or protected method the plugin declares must either still be
 * declared by something it inherits from, or be listed below as the plugin's
 * own. A new entry is a deliberate act, which is the point.
 */
class CraftOverrideTest extends Unit
{
    /**
     * Methods that belong to the plugin rather than to Craft, so having no
     * ancestor declaration is correct and expected.
     *
     * Keyed by `ShortClassName::methodName` to keep the list readable.
     *
     * @var array<string, string>
     */
    private const PLUGIN_OWNED = [
        // Controller actions. Craft routes to these by name, not by contract.
        'console\controllers\DefaultController::actionImport' => 'console command',
        'controllers\DefaultController::actionRefreshReviews' => 'CP route',
        'controllers\DefaultController::actionRefreshListings' => 'CP route',
        'controllers\DefaultController::actionDetails' => 'CP route',
        'controllers\PublicController::actionAuthenticate' => 'public route',
        'controllers\SettingsController::actionIndex' => 'CP route',
        'controllers\WebhookController::actionHandle' => 'webhook route',
        'controllers\WebhookController::verifyRequest' => 'webhook signature check',
        'controllers\WebhookController::handleReviewsPayoad' => 'webhook payload handler',
        'controllers\WebhookController::handleListingsPayoad' => 'webhook payload handler',

        // Element data accessors used by the plugin's own templates and fields.
        'elements\StratusListingElement::getHours' => 'plugin data accessor',
        'elements\StratusListingElement::setHours' => 'plugin data accessor',
        'elements\StratusListingElement::getHolidayHours' => 'plugin data accessor',
        'elements\StratusListingElement::setHolidayHours' => 'plugin data accessor',
        'elements\StratusListingElement::getRatings' => 'plugin data accessor',
        'elements\StratusListingElement::getAvgRating' => 'plugin data accessor',
        'elements\StratusListingElement::getMaxRating' => 'plugin data accessor',
        'elements\StratusListingElement::getFullAddress' => 'plugin data accessor',
        'elements\StratusListingElement::getFormattedHours' => 'plugin data accessor',
        'elements\StratusListingElement::getReviews' => 'eager-loaded relation',
        'elements\StratusListingElement::setReviews' => 'eager-loaded relation',
        'elements\StratusListingElement::getDetails' => 'feeds the Details element action',
        'elements\StratusReviewElement::getListing' => 'eager-loaded relation',
        'elements\StratusReviewElement::setListing' => 'eager-loaded relation',
        'elements\StratusReviewElement::getIcons' => 'plugin data accessor',
        'elements\StratusReviewElement::getDetails' => 'feeds the Details element action',
        'elements\StratusReviewElement::getContent' => 'the review body; unrelated to Craft 4 element content',

        // Custom element query params.
        'elements\db\StratusListingQuery::name' => 'query param',
        'elements\db\StratusListingQuery::type' => 'query param',
        'elements\db\StratusListingQuery::uuid' => 'query param',
        'elements\db\StratusReviewQuery::platform' => 'query param',
        'elements\db\StratusReviewQuery::platforms' => 'query param',
        'elements\db\StratusReviewQuery::rating' => 'query param',
        'elements\db\StratusReviewQuery::recommends' => 'query param',
        'elements\db\StratusReviewQuery::type' => 'query param',
        'elements\db\StratusReviewQuery::listing' => 'query param',
        'elements\db\StratusReviewQuery::listingId' => 'query param',
        'elements\db\StratusReviewQuery::uuid' => 'query param',
        'elements\db\StratusReviewQuery::datePublished' => 'query param',

        // GraphQL. GeneratorInterface only contracts generateTypes(); Craft's
        // own generators add generateType() by convention and so do these.
        'gql\types\generators\StratusListingType::generateType' => 'Craft convention, not contract',
        'gql\types\generators\StratusReviewType::generateType' => 'Craft convention, not contract',
        'helpers\Gql::canQueryStratusReviews' => 'plugin schema check',
        'helpers\Gql::canQueryStratusListings' => 'plugin schema check',

        // Protected helpers. The leading underscore is this plugin's house
        // style for "internal", not a Craft convention.
        'controllers\DefaultController::_getService' => 'plugin helper',
        'controllers\WebhookController::_getService' => 'plugin helper',
        'elements\StratusReviewElement::_wrapFieldHtml' => 'plugin helper',
        'elements\StratusReviewElement::_buildRatingHtml' => 'plugin helper',
        'elements\StratusReviewElement::_buildPlatformHtml' => 'plugin helper',
        'jobs\ImportListingsTask::_fetchListings' => 'plugin helper',
        'jobs\ImportListingsTask::_getService' => 'plugin helper',
        'jobs\ImportReviewsTask::_fetchReviews' => 'plugin helper',
        'jobs\ImportReviewsTask::_deleteReviewEntries' => 'plugin helper',
        'jobs\ImportReviewsTask::_getService' => 'plugin helper',

        // Jobs and services.
        'jobs\traits\MakesApiRequests::makeRequest' => 'plugin HTTP helper',
        'migrations\Install::createTables' => 'migration step',
        'migrations\Install::createIndexes' => 'migration step',
        'migrations\Install::addForeignKeys' => 'migration step',
        'migrations\Install::insertDefaultData' => 'migration step',
        'migrations\Install::removeTables' => 'migration step',
        'migrations\m230615_181038_add_location_address_and_hour_fields::addAddressAndTimezoneFields' => 'migration step',
        'migrations\m230615_181038_add_location_address_and_hour_fields::addHourFields' => 'migration step',
        'models\Settings::getApiKey' => 'settings accessor',
        'models\Settings::getBaseUrl' => 'settings accessor',
        'models\Settings::getWebhookSecret' => 'settings accessor',
        'models\Settings::getEnvVarName' => 'settings accessor',
        'models\Settings::getPlaintextSecrets' => 'settings accessor',
        'services\StratusService::importReviews' => 'plugin service API',
        'services\StratusService::importListings' => 'plugin service API',
        'services\StratusService::getReviewsLastPulledAt' => 'plugin service API',
        'services\StratusService::getTotalReviewCount' => 'plugin service API',
        'services\StratusService::getListingsLastPulledAt' => 'plugin service API',
        'services\StratusService::getTotalListingCount' => 'plugin service API',
        'services\StratusService::getListingByUuid' => 'plugin service API',
        'services\StratusService::getReviews' => 'plugin service API',
        'services\StratusService::getListings' => 'plugin service API',
        'services\StratusService::getPlatformName' => 'plugin service API',
        'services\StratusService::getPlatforms' => 'plugin service API',
        'services\StratusService::getPlatformIdentifiers' => 'plugin service API',
        'services\StratusService::moveSecretsToEnv' => 'plugin service API',
        'services\StratusService::syncListings' => 'plugin service API',
        'services\StratusService::syncReviews' => 'plugin service API',
        'services\StratusService::onBeforeSaveSettingsListener' => 'plugin event listener',
    ];

    /**
     * Overrides of Craft hooks that Craft no longer declares, deliberately kept
     * in the source. These are dead: Craft never calls them. Listing one here
     * rather than in PLUGIN_OWNED keeps the drift visible, and makes the suite
     * say so if Craft ever brings the hook back.
     *
     * Empty is the goal. Port the hook or delete it rather than adding to this.
     *
     * @var array<string, string>
     */
    private const DEAD_CRAFT_HOOKS = [
    ];

    public function testEveryOverrideStillHooksIntoCraft(): void
    {
        $orphans = [];

        foreach ($this->declaredMethods() as $key => $method) {
            if (isset(self::PLUGIN_OWNED[$key]) || isset(self::DEAD_CRAFT_HOOKS[$key])) {
                continue;
            }

            if (!$this->hasAncestorDeclaring($method)) {
                $orphans[] = $key;
            }
        }

        $this->assertSame([], $orphans, sprintf(
            "These methods override nothing in Craft or any other parent:\n  %s\n\n" .
            "Either Craft dropped the hook and the plugin needs porting, or the method is the " .
            "plugin's own and belongs in %s::PLUGIN_OWNED.",
            implode("\n  ", $orphans),
            static::class,
        ));
    }

    /**
     * Keeps the allowlist honest. A method that Craft has since introduced
     * should be treated as an override again, not left sitting in the list.
     */
    public function testAllowlistHasNoStaleEntries(): void
    {
        $declared = $this->declaredMethods();
        $stale = [];

        foreach (array_keys(self::PLUGIN_OWNED + self::DEAD_CRAFT_HOOKS) as $key) {
            if (!isset($declared[$key])) {
                $stale[] = "$key (no longer declared by the plugin)";
                continue;
            }

            if ($this->hasAncestorDeclaring($declared[$key])) {
                $stale[] = "$key (a parent declares this now, so it is a real override)";
            }
        }

        $this->assertSame([], $stale, sprintf(
            "Stale allowlist entries in %s:\n  %s",
            static::class,
            implode("\n  ", $stale),
        ));
    }

    /**
     * Reports the known-dead hooks so they cannot be forgotten, and fails if
     * Craft reintroduces one, which would mean the plugin is suddenly live
     * again with code written for an older Craft.
     */
    public function testKnownDeadHooksAreStillDead(): void
    {
        $declared = $this->declaredMethods();
        $revived = [];

        foreach (self::DEAD_CRAFT_HOOKS as $key => $note) {
            if (isset($declared[$key]) && $this->hasAncestorDeclaring($declared[$key])) {
                $revived[] = "$key ($note)";
            }
        }

        $this->assertSame([], $revived, sprintf(
            "Craft declares these again, so the plugin's versions are live overrides once more " .
            "and need reviewing against the current Craft behaviour:\n  %s",
            implode("\n  ", $revived),
        ));
    }

    /**
     * Every public and protected method the plugin declares itself, keyed by
     * `RelativeClassName::methodName`.
     *
     * @return array<string, ReflectionMethod>
     */
    private function declaredMethods(): array
    {
        $methods = [];

        foreach (PluginSource::classNames() as $name) {
            $class = new ReflectionClass($name);
            $relative = substr($name, strlen(PluginSource::ROOT_NAMESPACE));

            foreach ($class->getMethods() as $method) {
                if ($method->getDeclaringClass()->getName() !== $name || $method->isPrivate()) {
                    continue;
                }

                $methods["$relative::{$method->getName()}"] = $method;
            }
        }

        return $methods;
    }

    private function hasAncestorDeclaring(ReflectionMethod $method): bool
    {
        foreach (PluginSource::ancestors($method->getDeclaringClass()) as $ancestor) {
            if ($ancestor->hasMethod($method->getName())) {
                return true;
            }
        }

        return false;
    }
}
