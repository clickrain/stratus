<?php

namespace clickrain\stratus\elements\actions;

use Craft;
use craft\base\ElementAction;

/**
 * View represents a View element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Details extends ElementAction
{
    /**
     * @var string|null The trigger label
     */
    public ?string $label = null;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        if (!isset($this->label)) {
            $this->label = Craft::t('stratus', 'View Details');
        }
    }

    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return $this->label;
    }

    /**
     * @inheritdoc
     */
    public function getTriggerHtml(): ?string
    {
        Craft::$app->getView()->registerJsWithVars(fn($type) => <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: $type,
        bulk: false,
        activate: \$selectedItems => {
            const \$element = \$selectedItems.find('.element:first');

            const slideout = new Craft.CpScreenSlideout('stratus/default/details', {
                params: {
                    elementId: \$element.data('id'),
                    elementType: \$element.data('type'),
                },
            });

            slideout.on('close', () => {
                //Craft.elementIndex.updateElements();
            });

            slideout.on('load', () => {
                // these elements are readonly.  They can't be
                // created or updated by user interaction, so we
                // don't need the save button.
                slideout.\$saveBtn.addClass('hidden')
            });

            return slideout;
        },
    });
})();
JS, [static::class]);

        return null;
    }
}
