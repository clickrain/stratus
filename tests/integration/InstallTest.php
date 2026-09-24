<?php

namespace clickrain\stratus\tests\integration;

use Codeception\Test\Unit;
use Craft;
use clickrain\stratus\Stratus;

/**
 * Proves the plugin still installs into a real Craft application: the migration
 * runs, the tables land, and Craft registers the plugin's components. A Craft
 * release that changes migration or plugin bootstrapping fails here rather than
 * on someone's site.
 */
class InstallTest extends Unit
{
    protected \IntegrationTester $tester;

    public function testPluginIsInstalledAndLoaded(): void
    {
        $plugin = Craft::$app->getPlugins()->getPlugin('stratus');

        $this->assertInstanceOf(Stratus::class, $plugin);
        $this->assertSame('stratus', $plugin->handle);
    }

    public function testMigrationCreatedItsTables(): void
    {
        $schema = Craft::$app->getDb()->getSchema();
        $schema->refresh();

        foreach (['{{%stratus_reviews}}', '{{%stratus_listings}}'] as $table) {
            $this->assertNotNull(
                $schema->getTableSchema($table),
                "The install migration did not create $table.",
            );
        }
    }

    public function testServiceIsRegisteredOnThePlugin(): void
    {
        $this->assertInstanceOf(
            \clickrain\stratus\services\StratusService::class,
            Stratus::$plugin->stratus,
        );
    }
}
