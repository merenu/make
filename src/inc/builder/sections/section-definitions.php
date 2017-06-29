<?php
/**
 * @package Make
 */

if ( ! class_exists( 'TTFMAKE_Section_Definitions' ) ) :
/**
 * Collector for builder sections.
 *
 * @since 1.0.0.
 *
 * Class TTFMAKE_Section_Definitions
 */
class TTFMAKE_Section_Definitions {
	/**
	 * The one instance of TTFMAKE_Section_Definitions.
	 *
	 * @since 1.0.0.
	 *
	 * @var   TTFMAKE_Section_Definitions
	 */
	private static $instance;

	/**
	 * Instantiate or return the one TTFMAKE_Section_Definitions instance.
	 *
	 * @since  1.0.0.
	 *
	 * @return TTFMAKE_Section_Definitions
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->register();
		}

		return self::$instance;
	}

	public function register() {
		MAKE_Builder_Sections_Columns_Definition::register();
		// MAKE_Builder_Sections_Banner_Definition::register();
		// MAKE_Builder_Sections_Gallery_Definition::register();

		// Add the section JS
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}

	/**
	 * Enqueue the JS and CSS for the admin.
	 *
	 * @since  1.0.0.
	 *
	 * @param  string    $hook_suffix    The suffix for the screen.
	 * @return void
	 */
	public function admin_enqueue_scripts( $hook_suffix ) {
		// Only load resources if they are needed on the current page
		if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ) ) || ! ttfmake_post_type_supports_builder( get_post_type() ) ) {
			return;
		}

		// Add the section CSS
		wp_enqueue_style(
			'ttfmake-sections/css/sections.css',
			Make()->scripts()->get_css_directory_uri() . '/builder/sections/sections.css',
			array(),
			TTFMAKE_VERSION,
			'all'
		);
	}

	/**
	 * An array of defaults for all the Builder section settings
	 *
	 * @since  1.0.4.
	 *
	 * @return array    The section defaults.
	 */
	public function get_section_defaults() {
		// Note that this function does not do anything yet. It is part of an API refresh that is happening over time.
		$defaults = array();

		/**
		 * Filter the section defaults.
		 *
		 * @since 1.2.3.
		 *
		 * @param array    $defaults    The default section data
		 */
		return apply_filters( 'make_sections_defaults', $defaults );
	}

	/**
	 * Define the choices for section setting dropdowns.
	 *
	 * @since  1.0.4.
	 *
	 * @param  string    $key             The key for the section setting.
	 * @param  string    $section_type    The section type.
 	 * @return array                      The array of choices for the section setting.
	 */
	public function get_choices( $key, $section_type ) {
		$choices = array();

		/**
		 * Filter the section choices.
		 *
		 * @since 1.2.3.
		 *
		 * @param array    $choices         The default section choices.
		 * @param string   $key             The key for the data.
		 * @param string   $section_type    The type of section this relates to.
		 */
		return apply_filters( 'make_section_choices', $choices, $key, $section_type );
	}

	/**
	 * Define the sections settings.
	 *
	 * @since  1.8.10.
	 *
	 * @return array        The array of choices for the section setting.
	 */
	public function get_settings( $section_type = false ) {
		/**
		 * Filter the sections settings.
		 *
		 * @since 1.8.10.
		 *
		 * @param array    $settings        The section settings.
		 */
		$settings = apply_filters( 'make_sections_settings', array() );

		foreach ( $settings as $_section_type => $section_settings ) {
			/**
			 * Filter the default section data that is received.
			 *
			 * @since 1.8.10.
			 *
			 * @param string    $section_settings        Array of current section settings.
			 * @param string    $section_type            The type of section the data is for.
			 * @return mixed                             Array of settings if found; false if not found.
			 */
			$section_settings = apply_filters( 'make_section_settings', $section_settings, $_section_type );
			ksort( $section_settings, SORT_NUMERIC );
			$settings[$_section_type] = array_values( $section_settings );
		}

		if ( $section_type && isset( $settings[$section_type] ) ) {
			return $settings[$section_type];
		}

		return $settings;
	}
}
endif;

if ( ! function_exists( 'ttfmake_get_section_default' ) ) :
/**
 * Return the default value for a particular section setting.
 *
 * @since 1.0.4.
 *
 * @param  string    $key             The key for the section setting.
 * @param  string    $section_type    The section type.
 * @return mixed                      Default value if found; false if not found.
 */
function ttfmake_get_section_default( $key, $section_type ) {
	$defaults = ttfmake_get_section_definitions()->get_section_defaults();
	$value = false;

	if ( isset( $defaults[$section_type] ) && isset( $defaults[$section_type][$key] ) ) {
		$value = $defaults[$section_type][$key];
	}

	/**
	 * Filter the default section data that is received.
	 *
	 * @since 1.2.3.
	 *
	 * @param mixed     $value           The section value.
	 * @param string    $key             The key to get data for.
	 * @param string    $section_type    The type of section the data is for.
	 */
	return apply_filters( 'make_get_section_default', $value, $key, $section_type );
}
endif;

if ( ! function_exists( 'ttfmake_get_section_choices' ) ) :
/**
 * Wrapper function for TTFMAKE_Section_Definitions->get_choices
 *
 * @since 1.0.4.
 *
 * @param  string    $key             The key for the section setting.
 * @param  string    $section_type    The section type.
 * @return array                      The array of choices for the section setting.
 */
function ttfmake_get_section_choices( $key, $section_type ) {
	return ttfmake_get_section_definitions()->get_choices( $key, $section_type );
}
endif;

if ( ! function_exists( 'ttfmake_sanitize_section_choice' ) ) :
/**
 * Sanitize a value from a list of allowed values.
 *
 * @since 1.0.4.
 *
 * @param  string|int $value The current value of the section setting.
 * @param  string        $key             The key for the section setting.
 * @param  string        $section_type    The section type.
 * @return mixed                          The sanitized value.
 */
function ttfmake_sanitize_section_choice( $value, $key, $section_type ) {
	$choices         = ttfmake_get_section_choices( $key, $section_type );
	$allowed_choices = array_keys( $choices );

	if ( ! in_array( $value, $allowed_choices ) ) {
		$value = ttfmake_get_section_default( $key, $section_type );
	}

	/**
	 * Allow developers to alter a section choice during the sanitization process.
	 *
	 * @since 1.2.3.
	 *
	 * @param mixed     $value           The value for the section choice.
	 * @param string    $key             The key for the section choice.
	 * @param string    $section_type    The section type.
	 */
	return apply_filters( 'make_sanitize_section_choice', $value, $key, $section_type );
}
endif;

if ( ! function_exists( 'ttfmake_get_section_settings' ) ) :
/**
 * Return the default value for a particular section setting.
 *
 * @since 1.8.10.
 *
 * @param  string    $key             The key for the section setting.
 * @param  string    $section_type    The section type.
 * @return mixed                      Default value if found; false if not found.
 */
function ttfmake_get_sections_settings( $section_type = false ) {
	$settings = ttfmake_get_section_definitions()->get_settings( $section_type );
	return $settings;
}
endif;

/**
 * Instantiate or return the one TTFMAKE_Section_Definitions instance.
 *
 * @since  1.0.0.
 *
 * @return TTFMAKE_Section_Definitions
 */
function ttfmake_get_section_definitions() {
	return TTFMAKE_Section_Definitions::instance();
}

// Kick off the section definitions immediately
if ( is_admin() ) {
	add_action( 'after_setup_theme', 'ttfmake_get_section_definitions', 11 );
}
