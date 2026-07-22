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

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/vendor/emeraldion/emerails/include/common.inc.php';
require_once __DIR__ . '/helpers/application_helper.php';

use Emeraldion\EmeRails\Helpers\HTTP;
use Emeraldion\EmeRails\Helpers\Localization;

if (isset($_REQUEST['controller']) && !empty($_REQUEST['controller'])) {
    // Include controller class
    $controller_file = __DIR__ . "/controllers/{$_REQUEST['controller']}_controller.php";

    if (!file_exists($controller_file)) {
        HTTP::error(404);
    }
    require $controller_file;

    Localization::add_strings_table($_REQUEST['controller']);

    $main_controller_class = joined_lower_to_camel_case($_REQUEST['controller']) . 'Controller';

    // Instantiate main controller
    $main_controller = new $main_controller_class();

    // Set main controller's base path
    $main_controller->set_base_path(__DIR__);

    // Request rendering of the page
    // (If action didn't already do it before)
    $main_controller->render_page();
} else {
    HTTP::error(500);
}
