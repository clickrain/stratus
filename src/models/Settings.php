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


    public function rules(): array
    {
        return [
            [['apiKey'], 'required'],
        ];
    }
}
