<?php
/**
 * Stratus plugin for Craft CMS 3.x
 *
 * TODO: desc
 *
 * @link      clickrain.com
 * @copyright Copyright (c) 2022 Joseph Marikle
 */

namespace clickrain\stratus;

use clickrain\stratus\elements\StratusListingElement;
use clickrain\stratus\elements\StratusReviewElement;
use clickrain\stratus\fields\StratusListingField;
use clickrain\stratus\gql\queries\Stratus as StratusGqlQuery;
use clickrain\stratus\gql\interfaces\elements\StratusListing as StratusListingInterface;
use clickrain\stratus\gql\interfaces\elements\StratusReview as StratusReviewInterface;
use clickrain\stratus\services\StratusService;
use clickrain\stratus\models\Settings;
use clickrain\stratus\fields\StratusReviewField;
use clickrain\stratus\utilities\StratusUtility;

use Craft;
use craft\base\Element;
use craft\base\Plugin;
use craft\services\Gql;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\console\Application as ConsoleApplication;
use craft\events\ElementCriteriaEvent;
use craft\events\RegisterGqlQueriesEvent;
use craft\web\UrlManager;
use craft\services\Elements;
use craft\services\Fields;
use craft\services\Gc;
use craft\services\Utilities;
use craft\web\twig\variables\CraftVariable;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterElementActionsEvent;
use craft\events\RegisterGqlSchemaComponentsEvent;
use craft\events\RegisterGqlTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use yii\base\Event;
use yii\caching\TagDependency;

/**
 * Craft plugins are very much like little applications in and of themselves. We’ve made
 * it as simple as we can, but the training wheels are off. A little prior knowledge is
 * going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL,
 * as well as some semi-advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://docs.craftcms.com/v3/extend/
 *
 * @author    Joseph Marikle
 * @package   Stratus
 * @since     1.0.0
 *
 * @property  StratusService $stratusService
 * @property  Settings $settings
 * @method    Settings getSettings()
 */
class Stratus extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * Stratus::$plugin
     *
     * @var Stratus
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     *
     * @var string
     */
    public string $schemaVersion = '1.0.0';

    /**
     * Set to `true` if the plugin should have a settings view in the control panel.
     *
     * @var bool
     */
    public bool $hasCpSettings = true;

    /**
     * Set to `true` if the plugin should have its own section (main nav item) in the control panel.
     *
     * @var bool
     */
    public bool $hasCpSection = true;

    // Public Methods
    // =========================================================================

    /**
     * Set our $plugin static property to this class so that it can be accessed via
     * Stratus::$plugin
     *
     * Called after the plugin class is instantiated; do any one-time initialization
     * here such as hooks and events.
     *
     * If you have a '/vendor/autoload.php' file, it will be loaded for you automatically;
     * you do not need to load it in your init() method.
     *
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        // Add in our Twig extensions
        // Craft::$app->view->registerTwigExtension(new StratusTwigExtension());

        // Add in our console commands
        if (Craft::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'clickrain\stratus\console\controllers';
        }

        $this->setComponents([
            'stratus' => StratusService::class,
        ]);

        Event::on(
            Gc::class,
            Gc::EVENT_RUN,
            function() {
                /** @var \craft\services\Gc */
                $gcService = Craft::$app->getGc();
                $gcService->hardDelete('{{%stratus_reviews}}');
                $gcService->hardDelete('{{%stratus_listings}}');
            }
        );


        // Register our site routes
        // Event::on(
        //     UrlManager::class,
        //     UrlManager::EVENT_REGISTER_SITE_URL_RULES,
        //     function (RegisterUrlRulesEvent $event) {
        //         $event->rules['siteActionTrigger1'] = 'stratus/default';
        //     }
        // );

        // Register our CP routes
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules['cpActionTrigger1'] = 'stratus/default/refresh-reviews';
                $event->rules['cpActionTrigger2'] = 'stratus/default/refresh-listings';

                $event->rules['stratus/settings'] = 'stratus/settings/index';
            }
        );

        // Register our elements
        Event::on(
            Elements::class,
            Elements::EVENT_REGISTER_ELEMENT_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = StratusReviewElement::class;
                $event->types[] = StratusListingElement::class;
            }
        );

        // Register our fields
        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = StratusReviewField::class;
                $event->types[] = StratusListingField::class;
            }
        );

        Event::on(
            Gql::class,
            Gql::EVENT_REGISTER_GQL_TYPES,
            function(RegisterGqlTypesEvent $event) {
                $event->types[] = StratusListingInterface::class;
                $event->types[] = StratusReviewInterface::class;
            }
        );

        Event::on(
            Gql::class,
            Gql::EVENT_REGISTER_GQL_QUERIES,
            function(RegisterGqlQueriesEvent $event) {
                $event->queries = array_merge(
                    $event->queries,
                    StratusGqlQuery::getQueries()
                );
            }
        );

        Event::on(
            Gql::class,
            Gql::EVENT_REGISTER_GQL_SCHEMA_COMPONENTS,
            function(RegisterGqlSchemaComponentsEvent $event) {
                $event->queries[Craft::t('stratus', 'Stratus Reviews')] = [
                    'stratus.reviews:read' => ['label' => 'View Reviews'],
                    'stratus.listings:read' => ['label' => 'View Listings'],
                ];
            }
        );

        // Register our utilities
        Event::on(
            Utilities::class,
            Utilities::EVENT_REGISTER_UTILITY_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = StratusUtility::class;
            }
        );

        // Register our widgets
        // Event::on(
        //     Dashboard::class,
        //     Dashboard::EVENT_REGISTER_WIDGET_TYPES,
        //     function (RegisterComponentTypesEvent $event) {
        //         $event->types[] = StratusWidget::class;
        //     }
        // );

        // Register our variables
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;

                // Attach a service:
                $variable->set('stratus', StratusService::class);
            }
        );

        // Do something after we're installed
        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_INSTALL_PLUGIN,
            function (PluginEvent $event) {
                if ($event->plugin === $this) {
                    $cache = Craft::$app->getCache();
                    TagDependency::invalidate($cache, StratusService::REVIEWS_CACHE_TAG);
                    TagDependency::invalidate($cache, StratusService::LISTINGS_CACHE_TAG);

                    Craft::$app->getPlugins()->savePluginSettings($event->plugin, [
                        'baseUrl' => 'https://app.gostratus.io'
                    ]);
                }
            }
        );

        Event::on(
            StratusReviewElement::class,
            Element::EVENT_REGISTER_ACTIONS,
            function(RegisterElementActionsEvent $event) {
                $event->actions = [];
            }
        );

        Event::on(
            StratusListingElement::class,
            Element::EVENT_REGISTER_ACTIONS,
            function(RegisterElementActionsEvent $event) {
                $event->actions = [];
            }
        );

        Event::on(
            StratusReviewField::class,
            StratusReviewField::EVENT_DEFINE_SELECTION_CRITERIA,
            function(ElementCriteriaEvent $event) {
                if ($event->sender->sources !== '*') {
                    $enabledPlatforms = array_reduce($event->sender->sources, function ($carry, $source) {
                        if ($platform = preg_replace('/(?>platform:(.*))?.*?/', '$1', $source)) {
                            $carry[] = $platform;
                        }

                        return $carry;
                    }, []);

                    if (!empty($enabledPlatforms)) {
                        $event->criteria['platforms'] = $enabledPlatforms;
                    }
                }
            }
        );

        // Event::on(
        //     FieldLayout::class,
        //     FieldLayout::EVENT_DEFINE_NATIVE_FIELDS,
        //     static function(DefineFieldLayoutFieldsEvent $event) {
        //         /** @var FieldLayout $fieldLayout */
        //         $fieldLayout = $event->sender;

        //         Craft::dd('test');

        //         if ($fieldLayout->type === StratusReviewElement::class) {
        //             $event->fields[] = new TitleField([
        //                 'label' => 'My Title',
        //                 'mandatory' => true,
        //                 'instructions' => 'Enter a title.',
        //             ]);
        //         }
        //     }
        // );

        Event::on(
            self::class,
            self::EVENT_BEFORE_SAVE_SETTINGS,
            [StratusService::class, 'onBeforeSaveSettingsListener']
        );


/**
 * Logging in Craft involves using one of the following methods:
 *
 * Craft::trace(): record a message to trace how a piece of code runs. This is mainly for development use.
 * Craft::info(): record a message that conveys some useful information.
 * Craft::warning(): record a warning message that indicates something unexpected has happened.
 * Craft::error(): record a fatal error that should be investigated as soon as possible.
 *
 * Unless `devMode` is on, only Craft::warning() & Craft::error() will log to `craft/storage/logs/web.log`
 *
 * It's recommended that you pass in the magic constant `__METHOD__` as the second parameter, which sets
 * the category to the method (prefixed with the fully qualified class name) where the constant appears.
 *
 * To enable the Yii debug toolbar, go to your user account in the AdminCP and check the
 * [] Show the debug toolbar on the front end & [] Show the debug toolbar on the Control Panel
 *
 * http://www.yiiframework.com/doc-2.0/guide-runtime-logging.html
 */
        // Craft::info(
        //     Craft::t(
        //         'stratus',
        //         '{name} plugin loaded',
        //         ['name' => $this->name]
        //     ),
        //     __METHOD__
        // );
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates and returns the model used to store the plugin’s settings.
     *
     * @return \craft\base\Model|null
     */
    protected function createSettingsModel(): ?\craft\base\Model
    {
        return new Settings();
    }

    public function getSettingsResponse(): mixed
    {
        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('stratus/settings'));
    }

    /**
     * @inheritdoc
     */
    public function getCpNavItem(): ?array
    {
        $item = parent::getCpNavItem();
        $item['icon'] = '@clickrain/stratus/icon-mask.svg';
        $item['subnav'] = [
            'reviews' => ['label' => 'Reviews', 'url' => 'stratus/reviews'],
            'listings' => ['label' => 'Listings', 'url' => 'stratus/listings'],
            'settings' => ['label' => 'Plugin Settings', 'url' => 'stratus/settings'],
            'utility' => ['label' => 'Utilities → Import', 'url' => 'utilities/stratus'],
        ];
        return $item;
    }
}
