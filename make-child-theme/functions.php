<?php
/**
 * @package Make Child
 */

/**
 * The child theme version.
 *
 * This version number is used by the parent theme to determine how to handle
 * parent and child stylesheets. It is not used as a version parameter on the
 * child theme's stylesheet URL.
 *
 * @see MAKE_Setup_Scripts::enqueue_frontend_styles()
 */
define( 'TTFMAKE_CHILD_VERSION', '1.1.0' );

/**
 * Turn off the parent theme styles.
 *
 * If you would like to use this child theme to style Make from scratch, rather
 * than simply overriding specific style rules, remove the '//' from the
 * 'add_filter' line below. This will tell the theme not to enqueue the parent
 * stylesheet along with the child one.
 */
//add_filter( 'make_enqueue_parent_stylesheet', '__return_false' );

/**
 * Define a version number for the child theme's stylesheet.
 *
 * In order to prevent old versions of the child theme's stylesheet from loading
 * from a browser's cache, update the version number below each time changes are
 * made to the stylesheet.
 *
 * @uses MAKE_Setup_Scripts::update_version()
 */
function childtheme_style_version() {
	// Ensure the Make API is available.
	if ( ! function_exists( 'Make' ) ) {
		return;
	}

	// Version string to append to the child theme's style.css URL.
	$version = '1.0.0'; // <- Update this!

	Make()->scripts()->update_version( 'make-main', $version, 'style' );
}

add_action( 'wp_enqueue_scripts', 'childtheme_style_version', 20 );

/**
 * Add your custom theme functions here.
 */
