<?php
/**
 * iPin Modern — Navigation Walker
 *
 * A clean, accessible Walker_Nav_Menu implementation.
 * Bootstrap-free. Items with children get a disclosure <button>
 * after their link (WAI-ARIA disclosure navigation pattern): the
 * button owns aria-expanded/aria-controls and is toggled by
 * assets/js/theme.js, so submenus work on touch screens and for
 * keyboard and screen-reader users, not only on mouse hover.
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) exit;

/* -------------------------------------------------------
   NAV WALKER CLASS
   ------------------------------------------------------- */
class Ipin_Nav_Walker extends \Walker_Nav_Menu {

	/** Submenu id for the next start_lvl(); set by the parent's start_el(). */
	private string $pending_submenu_id = '';

	/**
	 * Start sub-menu list.
	 */
	public function start_lvl( &$output, $depth = 0, $args = null ): void {
		// No role="menu" — that ARIA role implies application-menu keyboard
		// semantics (arrow-key navigation) which this nav does not implement.
		// A plain <ul> inside <nav> is correct for site navigation links.
		$id = $this->pending_submenu_id ? ' id="' . esc_attr( $this->pending_submenu_id ) . '"' : '';
		$output .= "\n<ul class=\"dropdown-menu\"{$id}>\n";
		$this->pending_submenu_id = '';
	}

	/**
	 * Output a menu item — parents get a disclosure button after the link.
	 */
	public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ): void {
		$item      = $data_object;
		$item_html = '';
		parent::start_el( $item_html, $item, $depth, $args, $current_object_id );

		if ( ! empty( $item->is_dropdown ) ) {
			$sub_id = 'submenu-' . (int) $item->ID;
			$this->pending_submenu_id = $sub_id;

			$item_html .= sprintf(
				'<button type="button" class="submenu-toggle" aria-expanded="false" aria-controls="%1$s">'
				. '<span class="screen-reader-text">%2$s</span>'
				. '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5.3 8.3a1 1 0 0 1 1.4 0L12 13.6l5.3-5.3a1 1 0 1 1 1.4 1.4l-6 6a1 1 0 0 1-1.4 0l-6-6a1 1 0 0 1 0-1.4Z"/></svg>'
				. '</button>',
				esc_attr( $sub_id ),
				esc_html( sprintf(
					/* translators: %s = parent menu item title */
					__( 'Show submenu for %s', 'ipin-modern' ),
					wp_strip_all_tags( (string) $item->title )
				) )
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
