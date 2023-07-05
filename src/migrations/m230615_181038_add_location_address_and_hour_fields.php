<?php

namespace clickrain\stratus\migrations;

use Craft;
use craft\db\Migration;

/**
 * m230615_181038_add_location_address_and_hour_fields migration.
 */
class m230615_181038_add_location_address_and_hour_fields extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->addAddressAndTimezoneFields();
        $this->addHourFields();

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m230615_181038_add_location_address_and_hour_fields cannot be reverted.\n";
        return false;
    }

    // Protected Methods
    // =========================================================================

    /**
     * Add address fields
     *
     * @return void
     */
    protected function addAddressAndTimezoneFields()
    {
        if (!$this->db->columnExists('{{%stratus_listings}}', 'address')) {
            $this->addColumn('{{%stratus_listings}}', 'address', $this->string()->after('type'));
        }
        if (!$this->db->columnExists('{{%stratus_listings}}', 'address2')) {
            $this->addColumn('{{%stratus_listings}}', 'address2', $this->string()->after('address'));
        }
        if (!$this->db->columnExists('{{%stratus_listings}}', 'city')) {
            $this->addColumn('{{%stratus_listings}}', 'city', $this->string()->after('address2'));
        }
        if (!$this->db->columnExists('{{%stratus_listings}}', 'state')) {
            $this->addColumn('{{%stratus_listings}}', 'state', $this->string()->after('city'));
        }
        if (!$this->db->columnExists('{{%stratus_listings}}', 'zip')) {
            $this->addColumn('{{%stratus_listings}}', 'zip', $this->string()->after('state'));
        }
        if (!$this->db->columnExists('{{%stratus_listings}}', 'phone')) {
            $this->addColumn('{{%stratus_listings}}', 'phone', $this->string()->after('state'));
        }
        if (!$this->db->columnExists('{{%stratus_listings}}', 'timezone')) {
            $this->addColumn('{{%stratus_listings}}', 'timezone', $this->string()->after('zip'));
        }
    }

    /**
     * Add hour fields
     *
     * @return void
     */
    protected function addHourFields()
    {
        if (!$this->db->columnExists('{{%stratus_listings}}', 'hours')) {
            $this->addColumn('{{%stratus_listings}}', 'hours', $this->text()->after('phone'));
        }
        if (!$this->db->columnExists('{{%stratus_listings}}', 'holidayHours')) {
            $this->addColumn('{{%stratus_listings}}', 'holidayHours', $this->text()->after('hours'));
        }
    }
}
