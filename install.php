<?php
/* For license terms, see /license.txt */

/**
 * Install the Zoom Plugin
 * @package chamilo.plugin.zoom
 */

if (!api_is_platform_admin()) {
    die('You must have admin permissions to install plugins');
}
require_once __DIR__.'/config.php';
ZoomApiPlugin::create()->install();
