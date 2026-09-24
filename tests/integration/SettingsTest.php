<?php

namespace clickrain\stratus\tests\integration;

use Codeception\Test\Unit;
use clickrain\stratus\models\Settings;

/**
 * Settings resolve through Craft's App::parseEnv(), which is how the plugin
 * keeps API keys out of project config. If Craft changes environment variable
 * syntax or resolution, secrets silently resolve to the literal '$VAR' string
 * and every API call starts failing authentication.
 */
class SettingsTest extends Unit
{
    protected \IntegrationTester $tester;

    public function testSecretsResolveFromEnvironmentVariables(): void
    {
        putenv('STRATUS_TEST_KEY=resolved-key');
        $_SERVER['STRATUS_TEST_KEY'] = 'resolved-key';

        try {
            $settings = new Settings(['apiKey' => '$STRATUS_TEST_KEY']);
            $this->assertSame('resolved-key', $settings->getApiKey());
        } finally {
            putenv('STRATUS_TEST_KEY');
            unset($_SERVER['STRATUS_TEST_KEY']);
        }
    }

    public function testBaseUrlFallsBackToTheStratusDefault(): void
    {
        $this->assertSame('https://app.gostratus.io', (new Settings())->getBaseUrl());
    }

    public function testLiteralValuesArePassedThroughUnchanged(): void
    {
        $settings = new Settings(['webhookSecret' => 'literal-secret']);

        $this->assertSame('literal-secret', $settings->getWebhookSecret());
    }

    public function testEverySecretAttributeMapsToAnEnvVarName(): void
    {
        $settings = new Settings();

        foreach (array_keys(Settings::SECRET_ATTRIBUTES) as $attribute) {
            $this->assertNotNull(
                $settings->getEnvVarName($attribute),
                "$attribute has no environment variable name.",
            );
        }

        $this->assertNull($settings->getEnvVarName('baseUrl'), 'baseUrl is not a secret.');
    }

    /**
     * Drives the control panel warning that tells people their key is sitting
     * in project config in plain text.
     */
    public function testPlaintextSecretsAreReportedAndEnvReferencesAreNot(): void
    {
        $plaintext = new Settings(['apiKey' => 'sk-literal', 'webhookSecret' => '$STRATUS_WEBHOOK_SECRET']);

        $this->assertSame(['apiKey'], $plaintext->getPlaintextSecrets());
        $this->assertSame([], (new Settings())->getPlaintextSecrets());
    }

    public function testApiKeyIsRequired(): void
    {
        $settings = new Settings();

        $this->assertFalse($settings->validate());
        $this->assertArrayHasKey('apiKey', $settings->getErrors());
    }
}
