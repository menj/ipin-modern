<?php
/**
 * iPin Modern — Navigation Walker
 *
 * A clean, accessible Walker_Nav_Menu implementation.
 * Bootstrap-free. Supports multi-level dropdowns with ARIA attributes.
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) exit;

/* -------------------------------------------------------
   NAV WALKER CLASS
   ------------------------------------------------------- */
class Ipin_Nav_Walker extends \Walker_Nav_Menu {

	/**
	 * Start sub-menu list.
	 */
	public function start_lvl( &$output, $depth = 0, $args = null ): void {
		$output .= "\n<ul class=\"dropdown-menu\" role=\"menu\">\n";
	}

	/**
	 * Output a menu item — adds ARIA attributes to parent items.
	 */
	public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ): void {
		$item      = $data_object;
		$item_html = '';
		parent::start_el( $item_html, $item, $depth, $args, $current_object_id );

		if ( ! empty( $item->is_dropdown ) && 0 === $depth ) {
			$item_html = str_replace(
				'<a ',
				'<a aria-haspopup="true" aria-expanded="false" ',
				$item_html
			);
		}

		$output .= $item_html;
	}

	/**
	 * Flag items that have children so start_el can act on them.
	 */
	public function display_element( $element, &$children_elements, $max_depth, $depth, $args, &$output ): void {
		$element->is_dropdown = ! empty( $children_elements[ $element->ID ] )
			&& ( $max_depth === 0 || ( $depth + 1 ) < $max_depth );

		if ( $element->is_dropdown ) {
			$element->classes[] = 'has-dropdown';
		}

		parent::display_element( $element, $children_elements, $max_depth, $depth, $args, $output );
	}
}


/* -------------------------------------------------------
   CLEAN UP NAV MENU CSS CLASSES
   ------------------------------------------------------- */
function ipin_nav_css_class( array $classes, \WP_Post $item ): array {
	// Normalise WP's verbose current-page classes to just "active"
	$classes = preg_replace(
		'/(current(-menu-|[-_]page[-_])(item|parent|ancestor))/',
		'active',
		$classes
	);
	$classes = array_filter( $classes, static fn( string $c ) => '' !== trim( $c ) );
	return array_unique( array_values( $classes ) );
}
add_filter( 'nav_menu_css_class', 'ipin_nav_css_class', 10, 2 );
add_filter( 'nav_menu_item_id',   '__return_null' );


/* -------------------------------------------------------
   INJECT WALKER + SANE DEFAULTS INTO wp_nav_menu()
   ------------------------------------------------------- */
function ipin_nav_menu_args( array $args ): array {
	$args['walker']    ??= new Ipin_Nav_Walker();
	$args['container'] ??= false;
	$args['depth']     ??= 3;
	return $args;
}
add_filter( 'wp_nav_menu_args', 'ipin_nav_menu_args' );
