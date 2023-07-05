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

use clickrain\stratus\jobs\ImportListingsTask;
use clickrain\stratus\jobs\ImportReviewsTask;
use clickrain\stratus\services\StratusService;
use clickrain\stratus\Stratus;

use Craft;
use craft\helpers\Queue;
use craft\web\Controller;
use Exception;
use yii\base\Response;

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
class WebhookController extends Controller
{

    // Protected Properties
    // =========================================================================

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     *         The actions must be in 'kebab-case'
     * @access protected
     */
    protected array|bool|int $allowAnonymous =
        self::ALLOW_ANONYMOUS_LIVE | self::ALLOW_ANONYMOUS_OFFLINE;

    // Public Methods
    // =========================================================================

    /**
     * @param string $id the ID of this controller.
     * @param Module $module the module that this controller belongs to.
     * @param array $config name-value pairs that will be used to initialize the object properties.
     */
    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config = []);
        $this->enableCsrfValidation = false;
    }

    /**
     * @inheritDoc
     */
    public function beforeAction($action): bool
    {
        return parent::beforeAction($action);
    }

    /**
     * responds to webhook request containing reviews.
     *
     * @return Response
     */
    public function actionHandle(): Response
    {
        $response = [];
        try {
            $this->verifyRequest();

            $eventPayload = json_decode(Craft::$app->getRequest()->getRawBody(), true);

            match ($eventPayload['event']) {
                'reviews' => $this->handleReviewsPayoad($eventPayload['data']),
                'listings' => $this->handleListingsPayoad($eventPayload['data']),
                default => throw new Exception('unrecognized event type: ' . $eventPayload['event'])
            };
        } catch (\Throwable $th) {
            return $this->asSuccess('error', [
                'message' => $th->getMessage(),
                'body' => Craft::$app->getRequest()->getRawBody()
            ]);
        }
        return $this->asSuccess('success', $response);
    }

    protected function verifyRequest(): bool
    {
        $signature = Craft::$app->getRequest()->getHeaders()->get('Signature');
        $secret = Stratus::$plugin->getSettings()->webhookSecret;
        $payload = Craft::$app->getRequest()->getRawBody();

        if (!$signature) {
            throw new Exception('failed to find signature');
        }

        try {
            $signatureChallenge = hash_hmac('sha256', $payload, $secret);

            if ($signatureChallenge !== $signature) {
                throw new Exception('signature did not match');
            }
        } catch (Exception $exception) {
            throw new Exception('invalid signature: ' . $exception->getMessage());
        }

        if (empty($secret)) {
            throw new Exception('signing secret not set');
        }

        return true;
    }

    /**
     * Handle reviews payload
     *
     * @param array $data
     * @return void
     */
    protected function handleReviewsPayoad($data): void
    {
        Queue::push(
            job: new ImportReviewsTask([
                'reviews' => $data
            ]),
            queue: Craft::$app->getQueue()
        );
    }

    /**
     * Handle listings payload
     *
     * @param array $data
     * @return void
     */
    protected function handleListingsPayoad($data): void
    {
        foreach ($this->_getService()->syncListings($data) as $key => $value) {
            Craft::info('Updated Stratus record for ' . $value->stratusUuid, __METHOD__);
        }
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
