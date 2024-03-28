<?php

namespace F4\MT\Core;

/**
 * F4 Media Taxonomies Core Hooks
 *
 * All the WordPress hooks for the core module
 *
 * @since 1.0.0
 * @package	F4\MT\Core
 */
class Hooks {
	/**
	 * Initialize the hooks
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function init() {
		add_action('init', __NAMESPACE__ . '\\Hooks::core_loaded');
		add_action('init', __NAMESPACE__ . '\\Hooks::load_textdomain');
		add_action('init', __NAMESPACE__ . '\\Hooks::load_taxonomies', 99);
		add_action('admin_enqueue_scripts', __NAMESPACE__ . '\\Hooks::load_properties', 50);
		add_action('F4/MT/Core/set_constants', __NAMESPACE__ . '\\Hooks::set_default_constants', 98);

		add_action('admin_head', __NAMESPACE__ . '\\Hooks::add_custom_js');
		add_action('customize_controls_print_scripts', __NAMESPACE__ . '\\Hooks::add_custom_js');
		add_action('admin_enqueue_scripts', __NAMESPACE__ . '\\Hooks::admin_enqueue_scripts', 60);
		add_action('restrict_manage_posts', __NAMESPACE__ . '\\Hooks::add_media_list_filter');
		add_action('load-upload.php',  __NAMESPACE__ . '\\Hooks::run_bulk_action');
		add_action('admin_notices',  __NAMESPACE__ . '\\Hooks::show_bulk_action_notice');
		add_filter('attachment_fields_to_edit', __NAMESPACE__ . '\\Hooks::attachment_fields_to_edit', 1, 2);
		add_filter('update_post_term_count_statuses', __NAMESPACE__ . '\\Hooks::update_post_term_count_statuses', 10, 2);
		add_action('wp_ajax_f4-media-taxonomies-add-term', __NAMESPACE__ . '\\Hooks::ajax_add_term');
		add_action('wp_ajax_f4-media-taxonomies-search-terms', __NAMESPACE__ . '\\Hooks::ajax_search_terms');

		add_action('elementor/editor/after_enqueue_scripts', __NAMESPACE__ . '\\Hooks::load_properties', 50);
		add_action('elementor/editor/after_enqueue_scripts', __NAMESPACE__ . '\\Hooks::add_custom_js', 55);
		add_action('elementor/editor/after_enqueue_scripts', __NAMESPACE__ . '\\Hooks::admin_enqueue_scripts', 60);
	}

	/**
	 * Fires once the core module is loaded
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function core_loaded() {
		do_action('F4/MT/Core/set_constants');
		do_action('F4/MT/Core/loaded');
	}

	/**
	 * Load and filter all available attachment taxonomies
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 * @return array $attachment_taxonomies An array with all available attachment taxonomies
	 */
	public static function load_taxonomies() {
		$attachment_taxonomies_raw = get_taxonomies_for_attachments('objects');
		$attachment_taxonomies_raw = apply_filters('F4/MT/Core/load_taxonomies', $attachment_taxonomies_raw);

		$attachment_taxonomies = array();

		foreach($attachment_taxonomies_raw as $attachment_taxonomy) {
			if($attachment_taxonomy->show_ui) {
				$attachment_taxonomies[] = $attachment_taxonomy;
			}
		}

		Property::$taxonomies = $attachment_taxonomies;

		return $attachment_taxonomies;
	}

	/**
	 * Load properties
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function load_properties() {
		global $pagenow, $mode, $wp_scripts;

		Property::$has_bulk_action = $pagenow === 'upload.php' && $mode !== 'grid';
		Property::$has_filter = wp_script_is('media-views') || wp_script_is('acf-input') || apply_filters('F4/MT/Core/has_filter', false) || ($pagenow === 'upload.php' && $mode === 'grid') || apply_filters('cmb2_enqueue_js', true);
		Property::$has_assignment = Property::$has_filter;

		do_action('F4/MT/Core/load_properties');
	}

	/**
	 * Sets the default constants
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function set_default_constants() {
		if(!defined('DS')) {
			define('DS', DIRECTORY_SEPARATOR);
		}

		define('F4_MT_BULK_ACTION_PREFIX', 'f4_mt_toggle_');
	}

	/**
	 * Load plugin textdomain
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function load_textdomain() {
		load_plugin_textdomain('f4-media-taxonomies', false, plugin_basename(F4_MT_PATH . 'languages') . DS);
	}

	/**
	 * Add custom js into admin head
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function add_custom_js() {
		global $pagenow, $mode;

		// Abort if page has no bulk actions or filter
		if(!Property::$has_bulk_action && !Property::$has_filter && !Property::$has_assignment) {
			return;
		}

		// Get available media taxonomies
		$media_taxonomy_data = array(
			'taxonomies' => array(),
			'bulk_action_prefix' => F4_MT_BULK_ACTION_PREFIX
		);

		foreach(Property::$taxonomies as $media_taxonomy) {
			$media_taxonomy_data['taxonomies'][$media_taxonomy->name] = array(
				'slug' => $media_taxonomy->name,
				'terms' => Helpers::get_terms_hierarchical(array(
					'taxonomy' => $media_taxonomy->name,
					'hide_empty' => false
				)),
				'query_var' => $media_taxonomy->query_var,
				'labels' => array(
					'all_items' => $media_taxonomy->labels->all_items,
					'singular' => $media_taxonomy->labels->singular_name,
					'plural' => $media_taxonomy->labels->name,
					'bulk_title' => str_replace('%taxonomy%', $media_taxonomy->labels->name, __('Assign %taxonomy%', 'f4-media-taxonomies')),
					'search' => $media_taxonomy->labels->search_items,
					'add' => $media_taxonomy->labels->add_new_item,
					'search_hint' => str_replace('%chars%', '1', __('Please enter %chars% or more characters', 'f4-media-taxonomies'))
				)
			);
		}

		// Output media taxonomies as js code
		echo '<script>
			var f4MediaTaxonomy = ' . json_encode($media_taxonomy_data) . '
		</script>';
	}

	/**
	 * Enqueue admin script and styles
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function admin_enqueue_scripts() {
		global $pagenow, $mode;

		// Enqueue filters script
		if(Property::$has_filter) {
			wp_enqueue_script(
				'f4-media-taxonomies-filter',
				F4_MT_URL . 'assets/js/filter.js',
				array('media-views'),
				false,
				true
			);
		}

		// Enqueue bulk script
		if(Property::$has_bulk_action) {
			wp_enqueue_script(
				'f4-media-taxonomies-bulk',
				F4_MT_URL . 'assets/js/bulk.js',
				array(),
				false,
				true
			);
		}

		// Eneuque selecrize
		if(Property::$has_assignment) {
			wp_enqueue_script('selectize', 'https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.13.5/js/standalone/selectize.js', array(), '0.13.5');
			wp_enqueue_style('selectize', 'https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.13.5/css/selectize.min.css', array(), '0.13.5');

			wp_enqueue_script(
				'f4-media-taxonomies-assignment',
				F4_MT_URL . 'assets/js/assignment.js',
				array(),
				false,
				true
			);
		}

		// Enqueue styles
		if(Property::$has_bulk_action || Property::$has_filter || Property::$has_assignment) {
			wp_enqueue_style(
				'f4-media-taxonomies-styles',
				F4_MT_URL . 'assets/css/styles.css'
			);
		}
	}

	/**
	 * Add taxonomy filters to the media library list
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function add_media_list_filter() {
		global $pagenow;

		if($pagenow === 'upload.php') {
			foreach(Property::$taxonomies as $media_taxonomy) {
				$dropdown = wp_dropdown_categories(array(
					'taxonomy' => $media_taxonomy->name,
					'name' => $media_taxonomy->query_var,
					'id' => 'f4-media-taxonomies-' . $media_taxonomy->name . '-filter',
					'show_option_none' => $media_taxonomy->labels->all_items,
					'option_none_value' => '',
					'hide_empty' => false,
					'hierarchical' => $media_taxonomy->hierarchical,
					'orderby' => 'name',
					'order' => 'ASC',
					'show_count' => false,
					'value_field' => 'slug',
					'selected' => isset($_REQUEST[$media_taxonomy->query_var]) ? $_REQUEST[$media_taxonomy->query_var] : -1
				));
			}
		}
	}

	/**
	 * Run bulk action
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function run_bulk_action() {
		$is_bulk_action = isset($_REQUEST['action']) && strpos($_REQUEST['action'], F4_MT_BULK_ACTION_PREFIX) !== false;
		$is_bulk_action2 = isset($_REQUEST['action2']) && strpos($_REQUEST['action2'], F4_MT_BULK_ACTION_PREFIX) !== false;

		if(!isset($_REQUEST['media']) || (!$is_bulk_action && !$is_bulk_action2)) {
			return;
		}

		check_admin_referer('bulk-media');

		$media_action = $is_bulk_action2 ? $_REQUEST['action2'] : $_REQUEST['action'];
		$media_ids = array_map('intval', $_REQUEST['media']);
		$media_term_id = (int)substr($media_action, strlen(F4_MT_BULK_ACTION_PREFIX));
		$media_term = get_term($media_term_id);

		if(!is_object($media_term) || !is_a($media_term, 'WP_Term')) {
			return;
		}
		$backlink = remove_query_arg(array('action', 'action2', 'media', '_ajax_nonce', 'filter_action', 'toggle-taxonomy'));
		$backlink = add_query_arg('toggle-taxonomy', $media_term->taxonomy, $backlink);

		foreach($media_ids as $media_id) {
			$media_has_term = has_term($media_term_id, $media_term->taxonomy, $media_id);

			if($media_has_term) {
				wp_remove_object_terms($media_id, $media_term_id, $media_term->taxonomy);
			} else {
				wp_add_object_terms($media_id, $media_term_id, $media_term->taxonomy);
			}
		}

		wp_redirect($backlink);
		exit();
	}

	/**
	 * Show bulk action notice after complete
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function show_bulk_action_notice() {
		global $post_type, $pagenow;

		if(Property::$has_bulk_action && isset($_GET['toggle-taxonomy'])) {
			echo '<div class="notice notice-success is-dismissible"><p>' . __('Attachment(s) updated.', 'f4-media-taxonomies') . '</p></div>';
		}
	}

	/**
	 * Add fields to attachment
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 * @param array $fields An array with all fields to edit
	 * @param \WP_Post $post An object for the current post
	 * @return array $fields An array with all fields to edit
	 */
	public static function attachment_fields_to_edit($fields, $post) {
		foreach(Property::$taxonomies as $media_taxonomy) {
			$terms = get_the_terms($post, $media_taxonomy->name);

			$terms_options = array();

			if(is_array($terms) && !empty($terms)) {
				foreach($terms as $term) {
					$term_parent_ids = get_ancestors($term->term_id, $term->taxonomy, 'taxonomy');
					$term_parents = [];

					foreach($term_parent_ids as $term_parent) {
						array_unshift($term_parents, get_term($term_parent)->name);
					}

					$terms_options[$term->slug] = [
						'slug' => $term->slug,
						'name' => $term->name,
						'parents' => $term_parents,
						'sort' => strtolower(!empty($term_parents) ? implode('-', $term_parents) . '-' . $term->name : $term->name)
					];
				}

				uasort($terms_options, function($a, $b) {
					return strnatcasecmp($a['sort'], $b['sort']);
				});
			}

			$dropdown = '
				<input
					type="text"
					id="attachments-' . $post->ID .'-' . $media_taxonomy->name . '"
					name="attachments[' . $post->ID .'][' . $media_taxonomy->name . ']"
					data-selected-items= "' . esc_attr(json_encode($terms_options)) . '"
					value="' . implode(',', array_keys($terms_options)) . '"
				/>

				<script>
					if(typeof f4MediaTaxonomySelectize !== "undefined" && typeof f4MediaTaxonomy !== "undefined") {
						f4MediaTaxonomySelectize(\'#attachments-' . $post->ID .'-' . $media_taxonomy->name . '\', f4MediaTaxonomy.taxonomies[\'' . $media_taxonomy->name .'\']);
					}
				</script>
			';

			$fields[$media_taxonomy->name] = array(
				'show_in_edit' => false,
				'input' => 'html',
				'html' => $dropdown,
				'label' => $media_taxonomy->labels->name
			);
		}

		return $fields;
	}

	/**
	 * Add inherit post status for attachment taxonomy term count
	 *
	 * @since 1.0.16
	 * @access public
	 * @static
	 * @param array $statuses List of post statuses to include in the count
	 * @param \WP_Taxonomy $taxonomy The current taxonomy object
	 * @return array $statuses List of post statuses to include in the count
	 */
	public static function update_post_term_count_statuses($statuses, $taxonomy) {
		if(in_array('attachment', $taxonomy->object_type)) {
			$statuses[] = 'inherit';
		}

		return $statuses;
	}

	/**
	 * Add new term
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function ajax_add_term() {
		$new_term = wp_insert_term($_REQUEST['term_label'], $_REQUEST['taxonomy']);

		if(is_wp_error($new_term)) {
			die();
		}

		$new_term_obj = null;

		if(isset($new_term['term_id'])) {
			$new_term_obj = get_term($new_term['term_id']);
		}

		if(!is_wp_error($new_term_obj)) {
			wp_send_json(array(
				'new_term' => $new_term_obj
			));
		}

		die();
	}

	/**
	 * Search terms
	 *
	 * @since 1.1.0
	 * @access public
	 * @static
	 */
	public static function ajax_search_terms() {
		$terms_raw = Helpers::get_terms_hierarchical(array(
			'taxonomy' => $_REQUEST['taxonomy'],
			'hide_empty' => false
		));

		$terms = [];

		foreach($terms_raw as $term) {
			if(strripos($term->name, trim($_REQUEST['query'])) !== false) {
				$terms[] = [
					'value' => $term->slug,
					'text' => $term->name,
					'parents' => $term->parents
				];
			}
		}

		wp_send_json_success($terms);
	}
}
