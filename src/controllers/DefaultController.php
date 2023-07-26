<?php
/**
 * Stratus plugin for Craft CMS 3.x
 *
 * TODO: desc
 *
 * @link      clickrain.com
 * @copyright Copyright (c) 2022 Joseph Marikle
 */

namespace clickrain\stratus\controllers;

use clickrain\stratus\elements\StratusListingElement;
use clickrain\stratus\elements\StratusReviewElement;
use clickrain\stratus\services\StratusService;
use clickrain\stratus\Stratus;

use Craft;
use craft\web\Controller;
use yii\base\Response;
use yii\web\NotFoundHttpException;

/**
 * Default Controller
 *
 * Generally speaking, controllers are the middlemen between the front end of
 * the CP/website and your plugin’s services. They contain action methods which
 * handle individual tasks.
 *
 * A common pattern used throughout Craft involves a controller action gathering
 * post data, saving it on a model, passing the model off to a service, and then
 * responding to the request appropriately depending on the service method’s response.
 *
 * Action methods begin with the prefix “action”, followed by a description of what
 * the method does (for example, actionSaveIngredient()).
 *
 * https://craftcms.com/docs/plugins/controllers
 *
 * @author    Joseph Marikle
 * @package   Stratus
 * @since     1.0.0
 */
class DefaultController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    // protected $allowAnonymous = ['index', 'do-something'];

    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function beforeAction($action): bool
    {
        $this->requireCpRequest();

        return parent::beforeAction($action);
    }

    /**
     * Pulls reviews from stratus into the local database.
     *
     * @return Response
     */
    public function actionRefreshReviews(): Response
    {
        if (!$this->_getService()->importReviews()) {
            $this->setFailFlash(Craft::t('stratus', 'Failed to create update reviews job.'));

            return $this->redirectToPostedUrl();
        }

        return $this->redirectToPostedUrl();
    }

    /**
     * Pulls listings from stratus into the local database.
     *
     * @return Response
     */
    public function actionRefreshListings(): Response
    {
        if (!$this->_getService()->importListings()) {
            $this->setFailFlash(Craft::t('stratus', 'Failed to create update listings job.'));

            return $this->redirectToPostedUrl();
        }

        return $this->redirectToPostedUrl();
    }

    public function actionDetails(): Response
    {
        /** @var \craft\web\CpScreenResponseBehavior $screen */
        $screen = $this->asCpScreen();
        $type = Craft::$app->getRequest()->getRequiredParam('elementType');
        $id = Craft::$app->getRequest()->getRequiredParam('elementId');

        /** @var \craft\elements\db\ElementQuery */
        $elementQuery = (new $type)->find();

        /** @var \craft\base\Element */
        $element = $elementQuery->id($id)->one();

        // return not found error
        if (!$element) {
            throw new NotFoundHttpException('Element not found');
        }

        return $screen->contentTemplate('stratus/_components/ElementDetails', [
            'details' => $element->getDetails(),
        ]);
    }

    /**
     * Get the stratus service
     *
     * @return \clickrain\stratus\services\StratusService
     */
    protected function _getService(): StratusService
    {
        return Stratus::getInstance()->stratus;
    }
}
