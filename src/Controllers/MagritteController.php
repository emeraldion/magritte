<?php
/**
 *                                _ __  __
 *    ____ ___  ____ _____ ______(_) /_/ /____
 *   / __ `__ \/ __ `/ __ `/ ___/ / __/ __/ _ \
 *  / / / / / / /_/ / /_/ / /  / / /_/ /_/  __/
 * /_/ /_/ /_/\__,_/\__, /_/  /_/\__/\__/\___/
 *                 /____/
 *
 * (c) Claudio Procida 2026
 *
 * @format
 */

namespace Emeraldion\Magritte\Controllers;

use Emeraldion\EmeRails\Controllers\BaseController;
use Emeraldion\EmeRails\Config;
use Emeraldion\EmeRails\Db;

/**
 * @class PipelineController
 * @short Edit this controller's short description
 * @details Edit this controller's detailed description
 */
class MagritteController extends BaseController
{
    private $conn;

    /**
     * @fn init
     * @short Performs specialized initialization
     * @details You should use this method to do your custom initialization.
     */
    protected function init()
    {
        parent::init();

        $this->after_filter('close_connection');
    }

    protected function get_connection()
    {
        if (!isset($this->conn)) {
            $this->conn = Db::get_connection();
        }
        return $this->conn;
    }

    protected function close_connection()
    {
        if (isset($this->conn)) {
            Db::close_connection($this->conn);
            unset($this->conn);
        }
    }
}
