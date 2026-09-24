<?php

namespace clickrain\stratus\tests\integration;

use Codeception\Test\Unit;
use Craft;
use clickrain\stratus\Stratus;
use clickrain\stratus\controllers\WebhookController;
use ReflectionMethod;

/**
 * The webhook endpoint is unauthenticated apart from an HMAC signature, so its
 * verification is the only thing standing between the open internet and the
 * import queue. These tests pin that behaviour down.
 */
class WebhookTest extends Unit
{
    protected \IntegrationTester $tester;

    private const SECRET = 'webhook-signing-secret';

    protected function _before(): void
    {
        Craft::$app->getPlugins()->savePluginSettings(Stratus::$plugin, [
            'apiKey' => 'test-key',
            'webhookSecret' => self::SECRET,
        ]);
    }

    private function verify(string $body, ?string $signature): bool
    {
        $request = new \craft\web\Request();
        $request->setRawBody($body);

        if ($signature !== null) {
            $request->getHeaders()->set('Signature', $signature);
        }

        Craft::$app->set('request', $request);

        $controller = new WebhookController('webhook', Stratus::$plugin);
        $method = new ReflectionMethod($controller, 'verifyRequest');
        $method->setAccessible(true);

        return $method->invoke($controller);
    }

    public function testAValidSignatureIsAccepted(): void
    {
        $body = '{"event":"reviews","data":[]}';

        $this->assertTrue($this->verify($body, hash_hmac('sha256', $body, self::SECRET)));
    }

    public function testATamperedBodyIsRejected(): void
    {
        $signature = hash_hmac('sha256', '{"event":"reviews","data":[]}', self::SECRET);

        $this->expectExceptionMessage('signature did not match');
        $this->verify('{"event":"reviews","data":[{"injected":true}]}', $signature);
    }

    public function testASignatureFromTheWrongSecretIsRejected(): void
    {
        $body = '{"event":"reviews","data":[]}';

        $this->expectExceptionMessage('signature did not match');
        $this->verify($body, hash_hmac('sha256', $body, 'not-the-secret'));
    }

    public function testAMissingSignatureIsRejected(): void
    {
        $this->expectExceptionMessage('failed to find signature');
        $this->verify('{"event":"reviews","data":[]}', null);
    }

    public function testRequestsAreRejectedWhenNoSecretIsConfigured(): void
    {
        Craft::$app->getPlugins()->savePluginSettings(Stratus::$plugin, [
            'apiKey' => 'test-key',
            'webhookSecret' => '',
        ]);

        $body = '{"event":"reviews","data":[]}';

        $this->expectExceptionMessage('signing secret not set');
        $this->verify($body, hash_hmac('sha256', $body, self::SECRET));
    }

    /**
     * CSRF validation has to stay off or Stratus could never post here, but it
     * is easy to lose to a Craft change in controller construction.
     */
    public function testCsrfValidationIsDisabled(): void
    {
        $controller = new WebhookController('webhook', Stratus::$plugin);

        $this->assertFalse($controller->enableCsrfValidation);
    }

    /**
     * Stratus posts here with no Craft session, including while the site is
     * offline for a deploy. Losing either flag silently drops webhooks.
     */
    public function testTheEndpointAllowsAnonymousAccessLiveAndOffline(): void
    {
        $controller = new WebhookController('webhook', Stratus::$plugin);

        $property = new \ReflectionProperty($controller, 'allowAnonymous');
        $property->setAccessible(true);
        $allowAnonymous = $property->getValue($controller);

        $this->assertSame(
            WebhookController::ALLOW_ANONYMOUS_LIVE,
            $allowAnonymous & WebhookController::ALLOW_ANONYMOUS_LIVE,
        );
        $this->assertSame(
            WebhookController::ALLOW_ANONYMOUS_OFFLINE,
            $allowAnonymous & WebhookController::ALLOW_ANONYMOUS_OFFLINE,
        );
    }
}
