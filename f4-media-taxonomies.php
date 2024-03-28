<?php

/*
Plugin Name: F4 Media Taxonomies
Plugin URI: https://github.com/faktorvier/f4-media-taxonomies
Description: Add filters and bulk actions for attachment categories, tags and custom taxonomies.
Version: 1.1.4
Author: FAKTOR VIER
Author URI: https://www.f4dev.ch
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: f4-media-taxonomies
Domain Path: /languages/

F4 Media Taxonomies is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

F4 Media Taxonomies is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with F4 Media Taxonomies. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/

if(!defined('ABSPATH')) exit;

define('F4_MT_VERSION', '1.1.4');

define('F4_MT_SLUG', 'f4-media-taxonomies');
define('F4_MT_MAIN_FILE', __FILE__);
define('F4_MT_BASENAME', plugin_basename(F4_MT_MAIN_FILE));
define('F4_MT_PATH', dirname(F4_MT_MAIN_FILE) . DIRECTORY_SEPARATOR);
define('F4_MT_URL', plugins_url('/', F4_MT_MAIN_FILE));
define('F4_MT_PLUGIN_FILE', basename(F4_MT_BASENAME));
define('F4_MT_PLUGIN_FILE_PATH', F4_MT_PATH . F4_MT_PLUGIN_FILE);

// Add autoloader
spl_autoload_register(function($class) {
	$class = ltrim($class, '\\');
	$ns_prefix = 'F4\\MT\\';

	if(strpos($class, $ns_prefix) !== 0) {
		return;
	}

	$class_name = str_replace($ns_prefix, '', $class);
	$class_path = str_replace('\\', DIRECTORY_SEPARATOR, $class_name);
	$class_file = F4_MT_PATH . 'modules' . DIRECTORY_SEPARATOR . $class_path . '.php';

	if(file_exists($class_file)) {
		require_once $class_file;
	}
});

// Init modules
F4\MT\Core\Hooks::init();
