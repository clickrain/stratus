<?php

namespace clickrain\stratus\migrations;

use craft\db\Migration;

/**
 * m260911_100604_add_query_indexes migration.
 *
 * Every review query is ordered by platformPublishedDate, and the control panel
 * sources filter by platform, rating or reviewableType on top of that ordering.
 * None of those columns were indexed, so those views fell back to a full table
 * scan and a filesort. Listings are ordered by name for the same reason.
 */
class m260911_100604_add_query_indexes extends Migration
{
    /**
     * The indexes to add, as [table, columns].
     *
     * The filtered column leads each composite so that one index covers both
     * the filter and the ordering applied after it.
     */
    private const INDEXES = [
        ['{{%stratus_reviews}}', ['platformPublishedDate']],
        ['{{%stratus_reviews}}', ['platform', 'platformPublishedDate']],
        ['{{%stratus_reviews}}', ['rating', 'platformPublishedDate']],
        ['{{%stratus_reviews}}', ['reviewableType', 'platformPublishedDate']],
        ['{{%stratus_listings}}', ['name']],
    ];

    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        foreach (self::INDEXES as [$table, $columns]) {
            $this->createIndexIfMissing($table, $columns);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        foreach (self::INDEXES as [$table, $columns]) {
            $this->dropIndexIfExists($table, $columns);
        }

        return true;
    }
}
