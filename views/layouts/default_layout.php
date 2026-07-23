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

use Emeraldion\EmeRails\Config; ?>
<!DOCTYPE html>
<html>
    <head>
        <title><?php print $this->title; ?></title>
        <meta name="generator" content="EmeRails" />
        <link rel="icon" href="<?php print Config::get(
            'APPLICATION_ROOT'
        ); ?>assets/images/favicon.png" type="image/png" />
        <link rel="stylesheet" type="text/css" href="<?php print Config::get(
            'APPLICATION_ROOT'
        ); ?>assets/styles/styles.css" />
    </head>
    <body>
        <div id="main_content">
<?php print $this->content_for_layout; ?>
        </div>
    </body>
</html>
