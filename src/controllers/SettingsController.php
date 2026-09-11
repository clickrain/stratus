<?php

namespace clickrain\stratus\controllers;

use clickrain\stratus\Stratus;
use Craft;
use craft\web\Controller;
use craft\web\View;

class SettingsController extends Controller
{
    public function actionIndex()
    {
        $bodyParams = Craft::$app->getRequest()->getBodyParams();

        /** @var \craft\services\Plugins */
        $pluginsService = Craft::$app->getPlugins();
        /** @var \craft\behaviors\SessionBehavior */
        $sessionService = Craft::$app->getSession();

        $settings = Stratus::$plugin->getSettings();

        if (isset($bodyParams['settings'])) {
            $settings->setAttributes($bodyParams['settings'], false);

            // Keep credentials out of project config, which is version
            // controlled and synced between environments.
            $plaintext = Stratus::$plugin->stratus->moveSecretsToEnv($settings);

            if ($pluginsService->savePluginSettings(Stratus::$plugin, $settings->getAttributes())) {
                if ($plaintext) {
                    $sessionService->setError(Craft::t('stratus', 'Settings saved, but {names} could not be written to your .env file and will be stored in project config as plain text.', [
                        'names' => implode(' and ', array_map(
                            fn(string $attribute) => $settings->getAttributeLabel($attribute),
                            $plaintext
                        )),
                    ]));
                } else {
                    $sessionService->setNotice(Craft::t('stratus', 'Settings saved.'));
                }

                return $this->redirectToPostedUrl();
            }

            $sessionService->setError(Craft::t('stratus', 'Couldn\'t save settings.'));

            Craft::$app->getUrlManager()->setRouteParams([
                'settings' => $settings,
            ]);
        }

        return $this->renderTemplate('stratus/settings', [
            'settings' => $settings,
            'fullPageForm' => true,
        ], View::TEMPLATE_MODE_CP);
    }
}