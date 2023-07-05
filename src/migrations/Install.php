<?php
/**
 * Stratus plugin for Craft CMS 3.x
 *
 * TODO: desc
 *
 * @link      clickrain.com
 * @copyright Copyright (c) 2022 Joseph Marikle
 */

namespace clickrain\stratus\migrations;

use clickrain\stratus\Stratus;

use Craft;
use craft\config\DbConfig;
use craft\db\Connection;
use craft\db\Migration;

/**
 * Stratus Install Migration
 *
 * If your plugin needs to create any custom database tables when it gets installed,
 * create a migrations/ folder within your plugin folder, and save an Install.php file
 * within it using the following template:
 *
 * If you need to perform any additional actions on install/uninstall, override the
 * safeUp() and safeDown() methods.
 *
 * @author    Joseph Marikle
 * @package   Stratus
 * @since     1.0.0
 */
class Install extends Migration
{
    // Public Properties
    // =========================================================================

    /**
     * @var string The database driver to use
     */
    public $driver;

    // Public Methods
    // =========================================================================

    /**
     * This method contains the logic to be executed when applying this migration.
     * This method differs from [[up()]] in that the DB logic implemented here will
     * be enclosed within a DB transaction.
     * Child classes may implement this method instead of [[up()]] if the DB logic
     * needs to be within a transaction.
     *
     * @return boolean return a false value to indicate the migration fails
     * and should not proceed further. All other return values mean the migration succeeds.
     */
    public function safeUp()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        if ($this->createTables()) {
            $this->createIndexes();
            $this->addForeignKeys();
            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
            $this->insertDefaultData();
        }

        return true;
    }

    /**
     * This method contains the logic to be executed when removing this migration.
     * This method differs from [[down()]] in that the DB logic implemented here will
     * be enclosed within a DB transaction.
     * Child classes may implement this method instead of [[down()]] if the DB logic
     * needs to be within a transaction.
     *
     * @return boolean return a false value to indicate the migration fails
     * and should not proceed further. All other return values mean the migration succeeds.
     */
    public function safeDown()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();

        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * Creates the tables needed for the Records used by the plugin
     *
     * @return bool
     */
    protected function createTables()
    {
        $tablesCreated = false;

        // stratus_reviews table
        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%stratus_reviews}}');
        if ($tableSchema === null) {
            $tablesCreated = true;
            $this->createTable(
                '{{%stratus_reviews}}',
                [
                    'id' => $this->primaryKey(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'dateDeleted' => $this->dateTime()->null(),
                    'uid' => $this->uid(),

                    // Custom columns in the table
                    'platform' => $this->string()->notNull(),
                    'platformName' => $this->string()->notNull(),
                    'rating' => $this->integer(),
                    'recommends' => $this->boolean(),
                    'content' => $this->text(),
                    'author' => $this->string(),
                    'platformPublishedDate' => $this->dateTime()->notNull(),
                    'reviewableType' => $this->string()->notNull(),
                    'reviewableName' => $this->string()->notNull(),
                    'stratusUuid' => $this->string()->notNull(),
                    'stratusParentUuid' => $this->string()->notNull(),
                ]
            );
        }

        // stratus_listings table
        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%stratus_listings}}');
        if ($tableSchema === null) {
            $tablesCreated = true;
            $this->createTable(
                '{{%stratus_listings}}',
                [
                    'id' => $this->primaryKey(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'dateDeleted' => $this->dateTime()->null(),
                    'uid' => $this->uid(),

                    // Custom columns in the table
                    'name' => $this->string()->notNull(),
                    'type' => $this->string()->notNull(),
                    'address' => $this->string()->null(),
                    'address2' => $this->string()->null(),
                    'city' => $this->string()->null(),
                    'state' => $this->string()->null(),
                    'zip' => $this->string()->null(),
                    'phone' => $this->string()->null(),
                    'timezone' => $this->string()->null(),
                    'hours' => $this->text()->null(),
                    'holidayHours' => $this->text()->null(),
                    'reviewables' => $this->text()->null(),
                    'stratusUuid' => $this->string()->notNull(),
                ]
            );
        }

        return $tablesCreated;
    }

    /**
     * Creates the indexes needed for the Records used by the plugin
     *
     * @return void
     */
    protected function createIndexes()
    {
        // stratus_reviews table
        $this->createIndex(
            $this->db->getIndexName(),
            '{{%stratus_reviews}}',
            'stratusUuid',
            true
        );
        $this->createIndex(
            $this->db->getIndexName(),
            '{{%stratus_reviews}}',
            'stratusParentUuid',
            false
        );
        // stratus_listings table
        $this->createIndex(
            $this->db->getIndexName(),
            '{{%stratus_listings}}',
            'stratusUuid',
            true
        );
        // Additional commands depending on the db driver
        switch ($this->driver) {
            case Connection::DRIVER_MYSQL:
                break;
            case Connection::DRIVER_PGSQL:
                break;
        }
    }

    /**
     * Creates the foreign keys needed for the Records used by the plugin
     *
     * @return void
     */
    protected function addForeignKeys()
    {
        $this->addForeignKey(
            $this->db->getForeignKeyName('{{%stratus_reviews}}', 'id'),
            '{{%stratus_reviews}}', 'id', '{{%elements}}', 'id', 'CASCADE', null);

        $this->addForeignKey(
            $this->db->getForeignKeyName('{{%stratus_listings}}', 'id'),
            '{{%stratus_listings}}', 'id', '{{%elements}}', 'id', 'CASCADE', null);

        // stratus_stratusrecord table
        // $this->addForeignKey(
        //     $this->db->getForeignKeyName('{{%stratus_stratusrecord}}', 'siteId'),
        //     '{{%stratus_stratusrecord}}',
        //     'siteId',
        //     '{{%sites}}',
        //     'id',
        //     'CASCADE',
        //     'CASCADE'
        // );
    }

    /**
     * Populates the DB with the default data.
     *
     * @return void
     */
    protected function insertDefaultData()
    {
    }

    /**
     * Removes the tables needed for the Records used by the plugin
     *
     * @return void
     */
    protected function removeTables()
    {
        $this->dropTableIfExists('{{%stratus_reviews}}');
        $this->dropTableIfExists('{{%stratus_listings}}');
    }
}
