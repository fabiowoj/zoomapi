<?php
/* For license terms, see /license.txt */

/**
 * Uninstall the Zooms Plugin.
 *
 * @package chamilo.plugin.zoom
 */

if (!api_is_platform_admin()) {
    die('You must have admin permissions to uninstall plugins');
}

ZoomApiPlugin::create()->uninstall();
