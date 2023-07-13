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

            if ($pluginsService->savePluginSettings(Stratus::$plugin, $settings->getAttributes())) {
                $sessionService->setNotice(Craft::t('stratus', 'Settings saved.'));
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