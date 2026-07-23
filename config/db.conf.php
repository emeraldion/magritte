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

require_once __DIR__ . '/../vendor/emeraldion/emerails/include/common.inc.php';

use Emeraldion\EmeRails\Config;

switch (getenv('php_env')) {
    case 'prod':
    default:
        Config::set('DB_ADAPTER', default_to(getenv('DB_ADAPTER'), 'mysqli'));
        Config::set('DB_USER', default_to(getenv('DB_USER'), 'root'));
        Config::set('DB_PASS', default_to(getenv('DB_PASS'), 'root'));
        Config::set('DB_NAME', default_to(getenv('DB_NAME'), 'magritte'));
        Config::set('DB_HOST', default_to(getenv('DB_HOST'), 'localhost'));
}
Config::set('DB_CHARSET', default_to(getenv('DB_CHARSET'), 'utf8mb4'));
Config::set('DB_DEBUG', default_to(getenv('DB_DEBUG'), false));
