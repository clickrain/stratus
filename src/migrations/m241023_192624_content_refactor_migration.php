<?php

namespace clickrain\stratus\migrations;

use Craft;
use craft\db\Query;
use craft\db\Migration;

/**
 * m241023_192624_content_refactor_migration migration.
 */
class m241023_192624_content_refactor_migration extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // rename the content column to reviewContent in the stratus_reviews table
        $this->renameColumn('{{%stratus_reviews}}', 'content', 'reviewContent');

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        // rename the reviewContent column to content in the stratus_reviews table
        $this->renameColumn('{{%stratus_reviews}}', 'reviewContent', 'content');

        return false;
    }
}
