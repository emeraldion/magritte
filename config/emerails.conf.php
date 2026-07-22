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

// This flag controls additional debugging information and verbose error messages.
// Set it to false once you've finished development and are happy with the results.
Config::set('DEV_MODE', default_to(getenv('EMERAILS_DEV_MODE'), true));
Config::set('ERROR_REPORTING', default_to(getenv('EMERAILS_ERROR_REPORTING'), true));
Config::set('APPLICATION_ROOT', default_to(getenv('EMERAILS_APPLICATION_ROOT'), '/magritte/'));
Config::set('LANGUAGE_COOKIE', default_to(getenv('EMERAILS_LANGUAGE_COOKIE'), 'hl'));
Config::set('OBJECT_POOL_ENABLED', default_to(getenv('EMERAILS_OBJECT_POOL_ENABLED'), false));
Config::set('RENDER_DEBUG', default_to(getenv('EMERAILS_RENDER_DEBUG'), false));
Config::set('COMPONENTS_ENABLED', default_to(getenv('EMERAILS_COMPONENTS_ENABLED'), false));

// Since the introduction of method allow rules, you can specify which HTTP methods are allowed by controllers.
// By default, "dangerous" methods (PUT, POST, DELETE) are blocked by controllers.
//
// You can customize this config setting to tweak the default list of allowed methods, or set its value to '*'
// to restore the legacy behavior of allowing all methods unless explicitly blocked.
Config::set(
    'DEFAULT_ALLOWED_METHODS',
    default_to(getenv('EMERAILS_DEFAULT_ALLOWED_METHODS'), ['GET', 'HEAD', 'OPTIONS'])
);
