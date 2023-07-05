<?php

namespace clickrain\stratus\utilities;

use clickrain\stratus\assetbundles\stratus\StratusAsset;
use clickrain\stratus\Stratus;
use Craft;
use craft\base\Utility;

class StratusUtility extends Utility
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('stratus', 'Stratus Data Utility');
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return 'stratus';
    }

    /**
     * @inheritdoc
     */
    public static function iconPath(): ?string
    {
        return Craft::getAlias('@clickrain/stratus/icon-mask.svg');
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        $view = Craft::$app->getView();
        $plugin = Stratus::getInstance();

        $view->registerAssetBundle(StratusAsset::class);
        $view->registerJs('new Craft.Stratus.Utility();');

        return $view->renderTemplate('stratus/utilities', [
            'showEmptyState' => !$plugin->isInstalled || !$plugin->getSettings()->validate()
        ]);
    }
}
