<?php
/**
 * @package Make
 */

if ( ! class_exists( 'TTFMAKE_Settings_Overlay' ) ) :
/**
 * Handler for section overlays
 *
 * @since 1.9.0.
 *
 * Class TTFMAKE_Settings_Overlay
 */
class TTFMAKE_Settings_Overlay {

	private static $instance;

	public function __construct() {

	}

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function hook() {
		add_filter( 'make_builder_js_dependencies', array( $this, 'builder_dependencies' ), 10 );
		add_action( 'admin_head', array( $this, 'enqueue_styles' ) );
		add_action( 'admin_footer', array( $this, 'print_templates' ) );
	}

	public function builder_dependencies( $deps ) {
		if ( ! is_array( $deps ) ) {
			$deps = array();
		}

		$deps = array_diff( $deps, array( 'ttfmake-builder/js/views/overlay.js' ) );

		wp_register_script(
			'make-settings-overlay',
			get_stylesheet_directory_uri() . '/js/settings_overlay.js',
			array(
				'ttfmake-builder/js/views/section.js',
				'ttfmake-builder/js/views/overlay.js',
				'builder-views-item',
			),
			TTFMAKE_VERSION,
			true
		);

		wp_register_script(
			'make-content-overlay',
			get_stylesheet_directory_uri() . '/js/content_overlay.js',
			array( 'make-settings-overlay' ),
			TTFMAKE_VERSION,
			true
		);

		// Section settings
		$settings = ttfmake_get_sections_settings();
		wp_localize_script( 'make-settings-overlay', 'settings', $settings );

		return array_merge( $deps, array(
			'make-settings-overlay',
			'make-content-overlay'
		) );
	}

	public function enqueue_styles() {
		wp_enqueue_style(
			'make-settings-styles',
			get_stylesheet_directory_uri() . '/admin.css'
		);
	}

	public function print_templates() {
		global $hook_suffix, $typenow;

		// Only show when adding/editing pages
		if ( ! ttfmake_post_type_supports_builder( $typenow ) || ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ) )) {
			return;
		}

		// Singleton element for content editors
		get_template_part( '/inc/builder/core/templates/overlay', 'tinymce' );
		?>

		<script type="text/html" id="tmpl-ttfmake-settings">
		<div class="ttfmake-overlay-wrapper">
			<div class="ttfmake-overlay-dialog">
				<div class="ttfmake-overlay-header">
					<div class="ttfmake-overlay-window-head">
						<div class="ttfmake-overlay-title">Overlay title</div>
						<button type="button" class="media-modal-close ttfmake-overlay-close-discard">
							<span class="media-modal-icon">
						</button>
					</div>
				</div>
				<div class="ttfmake-overlay-body"></div>
				<div class="ttfmake-overlay-footer">
					<span class="ttfmake-overlay-close-update button button-primary button-large" aria-hidden="true"><?php esc_html_e( 'Update changes', 'make' ); ?></span>
				</div>
			</div>
		</div>
		</script>

		<script type="text/html" id="tmpl-ttfmake-content">
		<div class="ttfmake-overlay-wrapper">
			<div class="ttfmake-overlay-dialog">
				<div class="ttfmake-overlay-header">
					<div class="ttfmake-overlay-window-head">
						<div class="ttfmake-overlay-title">Edit content</div>
						<button type="button" class="media-modal-close ttfmake-overlay-close-discard">
							<span class="media-modal-icon">
						</button>
					</div>
				</div>
				<div class="ttfmake-overlay-body">
				<?php
				wp_editor( '', 'make', array(
					'tinymce' => array(
						'wp_autoresize_on' => false,
						'resize' => false,
					),
					'editor_height' => 270
				) );
				?>
				</div>
				<div class="ttfmake-overlay-footer">
					<span class="ttfmake-overlay-close-update button button-primary button-large" aria-hidden="true"><?php esc_html_e( 'Update changes', 'make' ); ?></span>
				</div>
			</div>
		</div>
		</script>

		<script type="text/html" id="tmpl-ttfmake-settings-divider">
		<span data-name="{{ data.name }}" class="{{ data.class }}">{{ data.label }}</span>
		</script>

		<script type="text/html" id="tmpl-ttfmake-settings-section_title">
		<input placeholder="{{ data.label }}" type="text" value="" class="{{ data.class }}" autocomplete="off"">
		</script>

		<script type="text/html" id="tmpl-ttfmake-settings-select">
		<label>{{ data.label }}</label>
		<select class="{{ data.class }}" {{ data.disabled ? 'disabled' : '' }}>
			<# for( var o in data.options ) { #>
			<option value="{{ o }}">{{ data.options[o] }}</option>
			<# } #>
		</select>
		<# if ( data.description ) { #>
		<div class="ttfmake-configuration-description">{{ data.description }}</div>
		<# } #>
		</script>

		<script type="text/html" id="tmpl-ttfmake-settings-checkbox">
		<label>{{ data.label }}</label>
		<input type="checkbox" value="1" class="{{ data.class }}">
		<# if ( data.description ) { #>
		<div class="ttfmake-configuration-description">{{ data.description }}</div>
		<# } #>
		</script>

		<script type="text/html" id="tmpl-ttfmake-settings-text">
		<label>{{ data.label }}</label>
		<input type="text" value="" class="{{ data.class }}">
		<# if ( data.description ) { #>
		<div class="ttfmake-configuration-description">{{ data.description }}</div>
		<# } #>
		</script>

		<script type="text/html" id="tmpl-ttfmake-settings-image">
		<label>{{ data.label }}</label>
		<div class="ttfmake-uploader">
			<div data-title="Set image" class="ttfmake-media-uploader-placeholder ttfmake-media-uploader-add {{ data.class }}"></div>
		</div>
		<# if ( data.description ) { #>
		<div class="ttfmake-configuration-description">{{ data.description }}</div>
		<# } #>
		</script>

		<script type="text/html" id="tmpl-ttfmake-settings-color">
		<label>{{ data.label }}</label>
		<input type="text" class="ttfmake-text-background-color ttfmake-configuration-color-picker {{ data.class }}" value="">
		<# if ( data.description ) { #>
		<div class="ttfmake-configuration-description">{{ data.description }}</div>
		<# } #>
		</script>

		<script type="text/html" id="tmpl-ttfmake-media-frame-remove-image">
		<div class="ttfmake-remove-current-image">
			<h3><?php esc_html_e( 'Current image', 'make' ); ?></h3>
			<a href="#" class="ttfmake-media-frame-remove-image">
				<?php esc_html_e( 'Remove Current Image', 'make' ); ?>
			</a>
		</div>
		</script>
		<?php
	}

}

endif;

if ( ! function_exists( 'ttfmake_get_section_overlay' ) ) :
/**
 * Instantiate or return the one TTFMAKE_Settings_Overlay instance.
 *
 * @since  1.9.0.
 *
 * @return TTFMAKE_Settings_Overlay
 */
function ttfmake_get_section_overlay() {
	return TTFMAKE_Settings_Overlay::instance();
}
endif;
