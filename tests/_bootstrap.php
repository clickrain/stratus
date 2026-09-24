<?php
/**
 * Codeception bootstrap.
 *
 * Craft's own test harness expects to run from inside a Craft project. This
 * plugin repo is not one, so we build the minimum skeleton Craft needs
 * (tests/_craft) and register the plugin with Craft's plugin service by hand.
 */

use craft\test\TestSetup;

ini_set('date.timezone', 'UTC');
date_default_timezone_set('UTC');

const CRAFT_TESTS_PATH = __DIR__;
const CRAFT_ROOT_PATH = __DIR__ . DIRECTORY_SEPARATOR . '_craft';
const CRAFT_STORAGE_PATH = CRAFT_ROOT_PATH . DIRECTORY_SEPARATOR . 'storage';
const CRAFT_TEMPLATES_PATH = CRAFT_ROOT_PATH . DIRECTORY_SEPARATOR . 'templates';
const CRAFT_CONFIG_PATH = CRAFT_ROOT_PATH . DIRECTORY_SEPARATOR . 'config';
const CRAFT_MIGRATIONS_PATH = CRAFT_ROOT_PATH . DIRECTORY_SEPARATOR . 'migrations';
const CRAFT_TRANSLATIONS_PATH = CRAFT_ROOT_PATH . DIRECTORY_SEPARATOR . 'translations';
const CRAFT_VENDOR_PATH = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor';

require_once CRAFT_VENDOR_PATH . DIRECTORY_SEPARATOR . 'autoload.php';

// TestSetup::configureCraft() realpath()s these, which returns false if they
// are missing and leaves Craft with broken aliases. Git does not reliably
// carry empty directories, so make sure they exist rather than trusting the
// checkout.
foreach ([CRAFT_STORAGE_PATH, CRAFT_STORAGE_PATH . '/logs', CRAFT_STORAGE_PATH . '/runtime'] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Craft reads database credentials through App::env(), which looks at real
// environment variables, so load tests/.env into the environment rather than
// into Codeception's params. CI sets these directly and ships no .env file.
if (file_exists(__DIR__ . '/.env')) {
    Dotenv\Dotenv::createUnsafeImmutable(__DIR__)->load();
}

// Craft discovers plugins from vendor/craftcms/plugins.php, which Composer's
// plugin installer writes when a plugin is pulled in as a dependency. Nothing
// writes it for the plugin you are standing in, so generate it here pointing
// back at src/. Without this, installPlugin('stratus') fails with an "invalid
// plugin handle" error that gives no hint about the cause.
(static function() {
    $repoRoot = dirname(__DIR__);
    $composer = json_decode(file_get_contents($repoRoot . '/composer.json'), true);
    $extra = $composer['extra'];

    $manifest = <<<MANIFEST
    <?php
    return [
        '{$composer['name']}' => [
            'class' => '{$extra['class']}',
            'basePath' => '{$repoRoot}/src',
            'handle' => '{$extra['handle']}',
            'aliases' => ['@clickrain/stratus' => '{$repoRoot}/src'],
            'name' => '{$extra['name']}',
            'version' => '0.0.0-dev',
            'schemaVersion' => '0.0.0',
            'developer' => 'Click Rain, Inc.',
        ],
    ];
    MANIFEST;

    $dir = $repoRoot . '/vendor/craftcms';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($dir . '/plugins.php', preg_replace('/^    /m', '', $manifest) . "\n");
})();

TestSetup::configureCraft();
