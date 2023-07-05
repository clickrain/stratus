<?php
/**
 * Stratus plugin for Craft CMS 3.x
 *
 * TODO: desc
 *
 * @link      clickrain.com
 * @copyright Copyright (c) 2022 Joseph Marikle
 */

namespace clickrain\stratus\console\controllers;

use clickrain\stratus\services\StratusService;
use clickrain\stratus\Stratus;

use Craft;
use craft\console\Controller;
use craft\helpers\Console;
use yii\console\ExitCode;

/**
 * Commands for handling importing Stratus data.
 *
 * @author    Joseph Marikle
 * @package   Stratus
 * @since     1.0.0
 */
class DefaultController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * Import reviews and listings via the stratus service.
     *
     * The first line of this method docblock is displayed as the description
     * of the Console Command in ./craft help
     *
     * @param boolean $fresh whether to force a full pull of all data.
     * If not set, only data fresher than "last pulled" values will
     * be returned from Stratus
     *
     * @return mixed
     */
    public function actionImport($fresh = false)
    {
        $code = ExitCode::OK;

        /** @var \clickrain\stratus\services\StratusService */
        $service = Stratus::getInstance()->stratus;

        // reviews
        if (!$service->importReviews($fresh)) {
            $this->stdout("Failed to create update reviews job\n", Console::FG_RED);
            $code = ExitCode::UNSPECIFIED_ERROR;
        }
        $this->stdout("Reviews job created.\n", Console::FG_GREEN);

        // listings
        if (!$service->importListings($fresh)) {
            $this->stdout("Failed to create update listings job.\n", Console::FG_RED);
            $code = ExitCode::UNSPECIFIED_ERROR;
        }
        $this->stdout("Listings job created.\n", Console::FG_GREEN);

        return $code;
    }
}
