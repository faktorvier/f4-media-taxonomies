<?php

/*
Plugin Name: F4 Media Taxonomies
Plugin URI: https://faktorvier.ch
Description: Add filters and bulk actions for attachments categories, tags and custom taxonomies.
Version: 1.0.0
Author: FAKTOR VIER
Author URI: https://faktorvier.ch
License: GPLv2
Text Domain: f4-media-taxonomies
*/

define('F4_MT_SLUG', 'f4-media-taxonomies');
define('F4_MT_TD', F4_MT_SLUG);
define('F4_MT_BASENAME', plugin_basename(__FILE__));
define('F4_MT_MAIN_FILE', __FILE__);
define('F4_MT_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);
define('F4_MT_URL', plugins_url('/', F4_MT_MAIN_FILE));
define('F4_MT_PLUGIN_FILE', basename(F4_MT_BASENAME));
define('F4_MT_PLUGIN_FILE_PATH', F4_MT_PATH . F4_MT_PLUGIN_FILE);
define('F4_MT_UPDATE_CHECK_URL', 'http://repository.faktorvier.ch/wordpress/index.php');

spl_autoload_register(function($class) {
	$class = ltrim($class, '\\');
	$ns_prefix = 'F4\\MT\\';

	if(strpos($class, $ns_prefix) !== 0) {
		return;
	}

	$class_name = str_replace($ns_prefix, '', $class);
	$class_path = str_replace('\\', DIRECTORY_SEPARATOR, $class_name );
	$class_file = F4_MT_PATH . $class_path . '.php';

	if(file_exists($class_file)) {
		require_once $class_file;
	}
});

// Init modules
F4\MT\Core\Hooks::init();

?>