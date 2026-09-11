<?php
/**
 * Stratus plugin for Craft CMS 3.x
 *
 * TODO: desc
 *
 * @link      clickrain.com
 * @copyright Copyright (c) 2022 Joseph Marikle
 */

namespace clickrain\stratus\models;

use clickrain\stratus\Stratus;

use Craft;
use craft\base\Model;
use craft\helpers\App;

/**
 * Stratus Settings Model
 *
 * This is a model used to define the plugin's settings.
 *
 * Models are containers for data. Just about every time information is passed
 * between services, controllers, and templates in Craft, it’s passed via a model.
 *
 * https://craftcms.com/docs/plugins/models
 *
 * @author    Joseph Marikle
 * @package   Stratus
 * @since     1.0.0
 */
class Settings extends Model
{
    /**
     * Settings that hold credentials, mapped to the environment variable each
     * should be stored in.
     *
     * Plugin settings are written to project config, which is version
     * controlled and synced between environments, so these are kept in the
     * environment and referenced by name instead.
     */
    public const SECRET_ATTRIBUTES = [
        'apiKey' => 'STRATUS_API_KEY',
        'webhookSecret' => 'STRATUS_WEBHOOK_SECRET',
    ];

    // Public Properties
    // =========================================================================

    /**
     * API key as provided by Stratus
     *
     * @var string
     */
    public $apiKey = '';

    /**
     * Base URL for Stratus API
     *
     * @var string
     */
    public $baseUrl = '';

    /**
     * Account ID within Stratus
     *
     * @var string
     */
    public $webhookSecret = '';

    // Public Methods
    // =========================================================================

    /**
     * Returns the validation rules for attributes.
     *
     * Validation rules are used by [[validate()]] to check if attribute values are valid.
     * Child classes may override this method to declare different validation rules.
     *
     * More info: http://www.yiiframework.com/doc-2.0/guide-input-validation.html
     *
     * @return array
     */

    public function getApiKey(): string
    {
        return App::parseEnv($this->apiKey);
    }

    public function getBaseUrl(): string
    {
        return App::parseEnv($this->baseUrl) ?: 'https://app.gostratus.io';
    }

    public function getWebhookSecret(): string
    {
        return App::parseEnv($this->webhookSecret);
    }

    /**
     * Returns the environment variable a given secret setting should be stored
     * in, or null if the setting does not hold a secret.
     *
     * @param string $attribute
     * @return string|null
     */
    public function getEnvVarName(string $attribute): ?string
    {
        return self::SECRET_ATTRIBUTES[$attribute] ?? null;
    }

    /**
     * Returns the secret settings currently held as literal values rather than
     * as an environment variable reference.
     *
     * Anything listed here is stored in project config in plain text.
     *
     * @return string[] the attribute names
     */
    public function getPlaintextSecrets(): array
    {
        $plaintext = [];

        foreach (array_keys(self::SECRET_ATTRIBUTES) as $attribute) {
            $value = (string)$this->$attribute;

            if ($value !== '' && !str_starts_with($value, '$')) {
                $plaintext[] = $attribute;
            }
        }

        return $plaintext;
    }


    public function rules(): array
    {
        return [
            [['apiKey'], 'required'],
        ];
    }
}
