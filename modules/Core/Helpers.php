<?php

namespace F4\MT\Core;

/**
 * F4 Media Taxonomies Core Helpers
 *
 * All the helpers for the core module
 *
 * @since 1.0.0
 * @package	F4\MT\Core
 */
class Helpers {
	/**
	 * Get terms sorted by hierarchy
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 * @param  array $args The arguments which should be passed to the get_terms function
	 * @param  int $parent The terms parent id (for recursive usage)
	 * @param  int $level The current level (for recursive usage)
	 * @param  array $parents An array with all the parent terms (for recursive usage)
	 * @return array $terms_all An array with all the terms for this taxonomy
	 */
	public static function get_terms_hierarchical($args = array(), $parent = 0, $level = 1, $parents = array()) {
		$terms_all = array();

		$args['parent'] = $args['child_of'] = $parent;

		$terms = get_terms($args);
		if (is_wp_error($terms)) {
			return $terms_all;
		}

		foreach((array) $terms as $term) {
			$term->level = $level;
			$term->parents = $parents;

			$term_parents = $parents;
			$term_parents[] = $term->name;

			$terms_all[] = $term;

			$terms_sub = self::get_terms_hierarchical($args, $term->term_id, $level + 1, $term_parents);

			if(!empty($terms_sub)) {
				$terms_all = array_merge($terms_all, $terms_sub);
			}
		}

		return $terms_all;
	}
}

