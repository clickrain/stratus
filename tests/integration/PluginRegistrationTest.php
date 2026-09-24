<?php

namespace clickrain\stratus\tests\integration;

use Codeception\Test\Unit;
use Craft;
use clickrain\stratus\Stratus;
use clickrain\stratus\elements\StratusListingElement;
use clickrain\stratus\elements\StratusReviewElement;
use clickrain\stratus\fields\StratusListingField;
use clickrain\stratus\fields\StratusReviewField;
use clickrain\stratus\utilities\StratusUtility;

/**
 * The plugin wires itself into Craft entirely through events in Stratus::init().
 * If Craft renames an event, moves it to another service or changes the event
 * object, the handler silently stops firing and the plugin loses a feature with
 * no error anywhere. These tests ask Craft what it actually ended up with.
 */
class PluginRegistrationTest extends Unit
{
    protected \IntegrationTester $tester;

    public function testElementTypesAreRegisteredWithCraft(): void
    {
        $types = Craft::$app->getElements()->getAllElementTypes();

        $this->assertContains(StratusReviewElement::class, $types);
        $this->assertContains(StratusListingElement::class, $types);
    }

    public function testFieldTypesAreRegisteredWithCraft(): void
    {
        $types = Craft::$app->getFields()->getAllFieldTypes();

        $this->assertContains(StratusReviewField::class, $types);
        $this->assertContains(StratusListingField::class, $types);
    }

    public function testUtilityIsRegisteredWithCraft(): void
    {
        $this->assertContains(StratusUtility::class, Craft::$app->getUtilities()->getAllUtilityTypes());
    }

    /**
     * Craft calls these statically when it builds an element index. A return
     * value of the wrong shape produces a broken index rather than an error.
     */
    public function testElementsDescribeThemselvesForTheControlPanel(): void
    {
        foreach ([StratusReviewElement::class, StratusListingElement::class] as $elementType) {
            $this->assertNotEmpty($elementType::displayName());
            $this->assertNotEmpty($elementType::pluralDisplayName());
            $this->assertNotEmpty($elementType::sources('index'), "$elementType has no index sources.");
            $this->assertNotEmpty($elementType::sortOptions(), "$elementType has no sort options.");
            $this->assertNotEmpty($elementType::tableAttributes(), "$elementType has no table attributes.");
            $this->assertInstanceOf(
                \craft\elements\conditions\ElementConditionInterface::class,
                $elementType::createCondition(),
            );
        }
    }

    /**
     * Every column offered in the element index has to be renderable, or the
     * index throws when someone enables that column.
     */
    public function testEveryTableAttributeCanBeRendered(): void
    {
        $service = Stratus::$plugin->stratus;
        iterator_to_array($service->syncListings([\clickrain\stratus\tests\_support\Payloads::listing()]));
        iterator_to_array($service->syncReviews([\clickrain\stratus\tests\_support\Payloads::review()]));

        $elements = [
            StratusReviewElement::find()->one(),
            StratusListingElement::find()->one(),
        ];

        foreach ($elements as $element) {
            foreach (array_keys($element::tableAttributes()) as $name) {
                $this->assertIsString(
                    $element->getAttributeHtml($name),
                    sprintf('%s cannot render its "%s" column.', $element::class, $name),
                );
            }
        }
    }

    public function testElementActionsAreRegistered(): void
    {
        foreach ([StratusReviewElement::class, StratusListingElement::class] as $elementType) {
            $actions = $elementType::actions('index');

            $this->assertContains(
                \clickrain\stratus\elements\actions\Details::class,
                $actions,
                "$elementType lost its Details action.",
            );
        }
    }

    public function testCpNavItemBuilds(): void
    {
        $item = Stratus::$plugin->getCpNavItem();

        $this->assertIsArray($item);
        $this->assertArrayHasKey('subnav', $item);
        $this->assertSame(
            ['reviews', 'listings', 'settings', 'utility'],
            array_keys($item['subnav']),
        );
    }

    /**
     * Craft's garbage collector hard deletes trashed rows. The plugin hooks it
     * so its own tables are cleaned up too; if the hook stops firing, deleted
     * rows accumulate forever and uuid uniqueness eventually breaks.
     */
    public function testGarbageCollectionHookIsRegistered(): void
    {
        $this->assertTrue(
            \yii\base\Event::hasHandlers(\craft\services\Gc::class, \craft\services\Gc::EVENT_RUN),
            'Nothing is listening to the garbage collection event.',
        );
    }
}
