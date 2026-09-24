<?php

use craft\helpers\App;

// Defaults match docker-compose.yml, so a clean checkout needs no tests/.env.
// STRATUS_TEST_DB_PORT moves both the published port and this DSN together;
// set CRAFT_DB_DSN instead to point at a database of your own.
$port = App::env('STRATUS_TEST_DB_PORT') ?: '33061';

return [
    'dsn' => App::env('CRAFT_DB_DSN') ?: "mysql:host=127.0.0.1;port=$port;dbname=stratus_test",
    'user' => App::env('CRAFT_DB_USER') ?: 'root',
    'password' => App::env('CRAFT_DB_PASSWORD') ?: '',
    'tablePrefix' => App::env('CRAFT_DB_TABLE_PREFIX') ?: '',
    // Craft still defaults to `utf8`, which MySQL 8 reads as utf8mb3 and then
    // rejects against its utf8mb4 default collation. Be explicit.
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
];
