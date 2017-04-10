<?php

namespace F4\MT\Core;

/**
 * Core Helpers
 *
 * All the WordPress helpers for the Core module
 *
 * @since 1.0.0
 * @package	F4\MT\Core
 */
class Helpers {
	private static $repositories = array();

	/**
	 * Initialize a singleton repository
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 * @param string $name The repository name including the namespace, but with slashes instead of backslashes (e.g. Module/Classes/Repository/Module)
	 * @return mixed
	 */
	public static function get_repository($name) {
		$repository_object = null;
		$name = str_replace('/', '\\', $name);

		if(isset(self::$repositories[$name])) {
			$repository_object = self::$repositories[$name];
		} else {
			$class_name = 'F4\\MT\\' . $name;
			$repository_object = new $class_name();
			self::$repositories[$name] = $repository_object;
		}

		return $repository_object;
	}

	/**
	 * Get plugin infos
	 *
	 * @since 1.0.0
	 * @access public
	 * @param string $info_name The info name to show
	 * @static
	 */
	public static function get_plugin_info($info_name) {
		if(!function_exists('get_plugins')) {
			require_once(ABSPATH . 'wp-admin/includes/plugin.php');
		}

		$info_value = null;
		$plugin_infos = get_plugin_data(F4_MT_PLUGIN_FILE_PATH);

		if(isset($plugin_infos[$info_name])) {
			$info_value = $plugin_infos[$info_name];
		}

		return $info_value;
	}

	/**
	 * Get remote repository infos
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function get_remote_repository_info() {
		if(!empty($_GET['force-check'])) {
			delete_transient(F4_MT_SLUG . '_get_remote_info');
		}

		$transient = get_transient(F4_MT_SLUG . '_get_remote_info');

		if($transient !== false) {
			return $transient;
		}

		// Get remote plugin infos
		$request = wp_remote_post(
			F4_MT_UPDATE_CHECK_URL,
			array(
				'body' => array(
					'action' => 'plugin_info',
					'arguments' => serialize(
						array(
							'slug' => F4_MT_SLUG
						)
					)
				)
			)
		);

		if(!is_wp_error($request) || wp_remote_retrieve_response_code($request) === 200) {
			$info = $request['body'];
		} else {
			$info = 0;
		}

		if(!empty($info)) {
			$info = unserialize($info);
			$timeout = 12 * HOUR_IN_SECONDS;
		} else {
			$info = 0;
			$timeout = 2 * HOUR_IN_SECONDS;
		}

		set_transient(F4_MT_SLUG . '_get_remote_info', $info, $timeout);

		return $info;
	}


	/**
	 * Get terms sorted by hierarchy
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function get_terms_hierarchical($taxonomy, $parent, $args, $level = 1, $parents = array()) {
		$terms_all = array();

		$args['parent'] = $args['child_of'] = $parent;

		$terms = get_terms($taxonomy, $args);

		foreach($terms as $term) {
			$term->level = $level;
			$term->parents = $parents;

			$term_parents = $parents;
			$term_parents[] = $term->name;

			$terms_all[] = $term;

			$terms_sub = self::get_terms_hierarchical($taxonomy, $term->term_id, $args, $level + 1, $term_parents);

			if(!empty($terms_sub)) {
				$terms_all = array_merge($terms_all, $terms_sub);
			}
		}

		return $terms_all;
	}
}

?>