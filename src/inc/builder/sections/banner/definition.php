<?php
/**
 * @package Make
 */

if ( ! class_exists( 'MAKE_Builder_Sections_Banner_Definition' ) ) :
/**
 * Section definition for Columns
 *
 * Class MAKE_Builder_Sections_Banner_Definition
 */
class MAKE_Builder_Sections_Banner_Definition {
	/**
	 * The one instance of MAKE_Builder_Sections_Banner_Definition.
	 *
	 * @var   MAKE_Builder_Sections_Banner_Definition
	 */
	private static $instance;

	/**
	 * Register the text section.
	 *
	 * Note that in 1.4.0, the "text" section was renamed to "columns". In order to provide good back compatibility,
	 * only the section label is changed to "Columns". All other internal references for this section will remain as
	 * "text".
	 *
	 * @return void
	 */
	public static function register() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function __construct() {
		add_filter( 'make_section_choices', array( $this, 'section_choices' ), 10, 3 );
		add_filter( 'make_sections_settings', array( $this, 'section_settings' ) );
		add_filter( 'make_sections_defaults', array( $this, 'section_defaults' ) );
		add_filter( 'make_get_section_json', array ( $this, 'get_section_json' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 20 );
		add_action( 'admin_footer', array( $this, 'print_templates' ) );

		ttfmake_add_section(
			'banner',
			__( 'Banner', 'make' ),
			Make()->scripts()->get_css_directory_uri() . '/builder/sections/images/banner.png',
			__( 'Display multiple types of content in a banner or a slider.', 'make' ),
			array( $this, 'save' ),
			array (
				'banner' => 'sections/banner/builder-template',
				'banner-slide' => 'sections/banner/builder-template-slide'
			),
			'sections/banner/frontend-template',
			300,
			get_template_directory() . '/inc/builder/'
		);
	}

	public function get_settings() {
		return array(
			100 => array(
				'type'    => 'divider',
				'label'   => __( 'General', 'make' ),
				'name'    => 'divider-general',
				'class'   => 'ttfmake-configuration-divider open',
			),
			200 => array(
				'type'  => 'section_title',
				'name'  => 'title',
				'label' => __( 'Enter section title', 'make' ),
				'class' => 'ttfmake-configuration-title ttfmake-section-header-title-input',
				'default' => Make()->section()->get_section_default( 'title', 'banner' ),
			),
			300 => array(
				'type'    => 'text',
				'label'   => __( 'Section height (px)', 'make' ),
				'name'    => 'height',
				'default' => Make()->section()->get_section_default( 'height', 'banner' ),
			),
			400 => array(
				'type'        => 'select',
				'label'       => __( 'Responsive behavior', 'make' ),
				'name'        => 'responsive',
				'default' => Make()->section()->get_section_default( 'responsive', 'banner' ),
				'description' => __( 'Choose how the Banner will respond to varying screen widths. Default is ideal for large amounts of written content, while Aspect is better for showing your images.', 'make' ),
				'options'     => Make()->section()->get_section_choices( 'responsive', 'banner' ),
			),
			500 => array(
				'type'  => 'divider',
				'label' => __( 'Background', 'make' ),
				'name'  => 'divider-background',
				'class' => 'ttfmake-configuration-divider',
			),
			600 => array(
				'type'  => 'image',
				'name'  => 'background-image',
				'label' => __( 'Background image', 'make' ),
				'class' => 'ttfmake-configuration-media',
				'default' => Make()->section()->get_section_default( 'background-image', 'banner' ),
			),
			700 => array(
				'type'  => 'select',
				'name'  => 'background-position',
				'label' => __( 'Position', 'make' ),
				'class' => 'ttfmake-configuration-media-related',
				'default' => Make()->section()->get_section_default( 'background-position', 'banner' ),
				'options' => Make()->section()->get_section_choices( 'background-position', 'banner' ),
			),
			800 => array(
				'type'    => 'select',
				'name'    => 'background-style',
				'label'   => __( 'Display', 'make' ),
				'class'   => 'ttfmake-configuration-media-related',
				'default' => Make()->section()->get_section_default( 'background-style', 'banner' ),
				'options' => Make()->section()->get_section_choices( 'background-style', 'banner' ),
			),
			900 => array(
				'type'    => 'checkbox',
				'label'   => __( 'Darken', 'make' ),
				'name'    => 'darken',
				'default' => Make()->section()->get_section_default( 'darken', 'banner' ),
			),
			1000 => array(
				'type'    => 'color',
				'label'   => __( 'Background color', 'make' ),
				'name'    => 'background-color',
				'class'   => 'ttfmake-gallery-background-color ttfmake-configuration-color-picker',
				'default' => Make()->section()->get_section_default( 'background-color', 'banner' ),
			),
			1100 => array(
				'type'    => 'divider',
				'label'   => __( 'Slideshow', 'make' ),
				'name'    => 'divider-slideshow',
				'class'   => 'ttfmake-configuration-divider',
			),
			1200 => array(
				'type'    => 'checkbox',
				'label'   => __( 'Navigation arrows', 'make' ),
				'name'    => 'arrows',
				'default' => Make()->section()->get_section_default( 'arrows', 'banner' ),
			),
			1300 => array(
				'type'    => 'checkbox',
				'label'   => __( 'Navigation dots', 'make' ),
				'name'    => 'dots',
				'default' => Make()->section()->get_section_default( 'dots', 'banner' ),
			),
			1400 => array(
				'type'    => 'checkbox',
				'label'   => __( 'Autoplay slideshow', 'make' ),
				'name'    => 'autoplay',
				'default' => Make()->section()->get_section_default( 'autoplay', 'banner' ),
			),
			1500 => array(
				'type'    => 'select',
				'label'   => __( 'Speed', 'make' ),
				'name'    => 'delay',
				'default' => Make()->section()->get_section_default( 'delay', 'banner' ),
				'options' => Make()->section()->get_section_choices( 'delay', 'banner' )
			),
			1600 => array(
				'type'    => 'select',
				'label'   => __( 'Transition effect', 'make' ),
				'name'    => 'transition',
				'default' => Make()->section()->get_section_default( 'transition', 'banner' ),
				'options' => Make()->section()->get_section_choices( 'transition', 'banner' ),
			),
		);
	}

	public function get_slide_settings() {
		$inputs = array(
			100 => array(
				'type'    => 'select',
				'name'    => 'alignment',
				'label'   => __( 'Content position', 'make' ),
				'default' => Make()->section()->get_section_default( 'alignment', 'banner-slide' ),
				'options' => array(
					'none'  => __( 'None', 'make' ),
					'left'  => __( 'Left', 'make' ),
					'right' => __( 'Right', 'make' ),
				),
			),
			200 => array(
				'type'    => 'checkbox',
				'label'   => __( 'Darken', 'make' ),
				'name'    => 'darken',
				'default' => Make()->section()->get_section_default( 'darken', 'banner-slide' )
			),
			300 => array(
				'type'    => 'color',
				'label'   => __( 'Background color', 'make' ),
				'name'    => 'background-color',
				'class'   => 'ttfmake-gallery-background-color ttfmake-configuration-color-picker',
				'default' => Make()->section()->get_section_default( 'background-color', 'banner-slide' )
			),
		);
		/**
		 * Filter the definitions of the Banner slide configuration inputs.
		 *
		 * @since 1.4.0.
		 *
		 * @param array    $inputs    The input definition array.
		 */
		$inputs = apply_filters( 'make_banner_slide_configuration', $inputs );
		// Sort the config in case 3rd party code added another input
		ksort( $inputs, SORT_NUMERIC );
		return $inputs;
	}

	/**
	 * Define settings for this section
	 *
	 * @since 1.8.11.
	 *
	 * @hooked filter make_sections_settings
	 *
	 * @param array $settings   The existing array of section settings.
	 *
	 * @return array             The modified array of section settings.
	 */
	public function section_settings( $settings ) {
		$settings['banner'] = $this->get_settings();
		$settings['banner-slide'] = $this->get_slide_settings();
		return $settings;
	}

	/**
	 * Add new section choices.
	 *
	 * @since 1.8.8.
	 *
	 * @hooked filter make_section_choices
	 *
	 * @param array  $choices         The existing choices.
	 * @param string $key             The key for the section setting.
	 * @param string $section_type    The section type.
	 *
	 * @return array                  The choices for the particular section_type / key combo.
	 */
	public function section_choices( $choices, $key, $section_type ) {
		if ( count( $choices ) > 1 || ! in_array( $section_type, array( 'banner' ) ) ) {
			return $choices;
		}

		$choice_id = "$section_type-$key";

		switch ( $choice_id ) {
			case 'banner-delay':
				$choices = array(
					'9000' => __( 'Slow', 'make' ),
					'6000' => __( 'Default', 'make' ),
					'3000' => __( 'Fast', 'make' ),
				);
				break;

			case 'banner-transition':
				$choices = array(
					'scrollHorz' => __( 'Slide horizontal', 'make' ),
					'fade' => __( 'Fade', 'make' ),
					'none' => __( 'None', 'make' ),
				);
				break;

			case 'banner-responsive' :
				$choices = array(
					'balanced' => __( 'Default', 'make' ),
					'aspect'   => __( 'Aspect', 'make' ),
				);
				break;

			case 'banner-background-style':
				$choices = array(
					'tile'  => __( 'Tile', 'make' ),
					'cover' => __( 'Cover', 'make' ),
					'contain' => __( 'Contain', 'make' ),
				);
				break;

			case 'banner-background-position' :
				$choices = array(
					'center-top'  => __( 'Top', 'make' ),
					'center-center' => __( 'Center', 'make' ),
					'center-bottom' => __( 'Bottom', 'make' ),
					'left-center'  => __( 'Left', 'make' ),
					'right-center' => __( 'Right', 'make' )
				);
				break;
		}

		return $choices;
	}

	/**
	 * Get default values for banner section
	 *
	 * @since 1.8
	 *
	 * @return array
	 */
	public function get_defaults() {
		return array(
			'section-type' => 'banner',
			'title' => '',
			'arrows' => 1,
			'dots' => 1,
			'autoplay' => 1,
			'delay' => 6000,
			'transition' => 'scrollHorz',
			'height' => 600,
			'responsive' => 'balanced',
			'background-image' => '',
			'background-position' => 'center-center',
			'darken' => 0,
			'background-style' => 'cover',
			'background-color' => '',
		);
	}

	/**
	 * Get default values for banner slide
	 *
	 * @since 1.8
	 *
	 * @return array
	 */
	public function get_slide_defaults() {
		return array(
			'section-type' => 'banner-slide',
			'alignment' => 'none',
			'darken' => 0,
			'background-color' => '',
			'content' => '',
			'background-image' => '',
		);
	}

	/**
	 * Extract the setting defaults and add them to Make's section defaults system.
	 *
	 * @since 1.6.0.
	 *
	 * @hooked filter make_sections_defaults
	 *
	 * @param array $defaults    The existing array of section defaults.
	 *
	 * @return array             The modified array of section defaults.
	 */
	public function section_defaults( $defaults ) {
		$defaults['banner'] = $this->get_defaults();
		$defaults['banner-slide'] = $this->get_slide_defaults();

		return $defaults;
	}

	/**
	 * Filter the json representation of this section.
	 *
	 * @since 1.8.0.
	 *
	 * @hooked filter make_get_section_json
	 *
	 * @param array $defaults    The array of data for this section.
	 *
	 * @return array             The modified array to be jsonified.
	 */
	public function get_section_json( $data ) {
		if ( $data['section-type'] == 'banner' ) {
			$data = wp_parse_args( $data, $this->get_defaults() );
			$image = ttfmake_get_image_src( $data['background-image'], 'large' );

			if ( isset( $image[0] ) ) {
				$data['background-image-url'] = $image[0];
			} else {
				$data['background-image'] = '';
			}

			if ( isset( $data['banner-slides'] ) && is_array( $data['banner-slides'] ) ) {
				foreach ( $data['banner-slides'] as $s => $slide ) {
					$slide = wp_parse_args( $slide, $this->get_slide_defaults() );

					// Handle legacy data layout
					$id = isset( $slide['id'] ) ? $slide['id']: $s;
					$slide['id'] = $id;

					/*
					 * Back compatibility code for slide background images.
					 *
					 * @since 1.8.9.
					 */
					if ( isset( $slide['image-id'] ) && '' !== $slide['image-id'] ) {
						$slide['background-image'] = $slide['image-id'];
					}

					$slide_image = ttfmake_get_image_src( $slide['background-image'], 'large' );

					if ( isset( $slide_image[0] ) ) {
						$slide['background-image-url'] = $slide_image[0];
					} else {
						$slide['background-image'] = '';
					}

					$data['banner-slides'][$s] = $slide;
				}

				if ( isset( $data['banner-slide-order'] ) ) {
					$ordered_items = array();

					foreach ( $data['banner-slide-order'] as $item_id ) {
						array_push( $ordered_items, $data['banner-slides'][$item_id] );
					}

					$data['banner-slides'] = $ordered_items;
					unset( $data['banner-slide-order'] );
				}

				/*
				 * Back compatibility code for changing negative phrased checkboxes.
				 *
				 * @since 1.8.8.
				 */
				if ( isset( $data['hide-dots'] ) ) {
					$data['dots'] = ( absint( $data['hide-dots'] ) == 1 ) ? 0 : 1;
				}

				if ( isset( $data['hide-arrows'] ) ) {
					$data['arrows'] = ( absint( $data['hide-arrows'] ) == 1 ) ? 0 : 1;
				}

				/*
				 * Back compatibility for speed (time between slides). Set it to default value when
				 * the original value is other than one found in available section choices.
				 *
				 * @since 1.8.8.
				 */
				 if( isset( $data['delay'] ) && !in_array( $data['delay'], Make()->section()->get_section_choices( 'delay', 'banner' ) ) ) {
					 $data['delay'] = Make()->section()->get_section_default( 'delay', 'banner' );
				 }
			}
		}

		return $data;
	}

	/**
	 * Save the data for the section.
	 *
	 * @param  array    $data    The data from the $_POST array for the section.
	 * @return array             The cleaned data.
	 */
	public function save( $data ) {
		$data = wp_parse_args( $data, $this->get_defaults() );

		$clean_data = array(
			'id' => $data['id'],
			'section-type' => $data['section-type'],
			'state' => $data['state'],
		);
		$clean_data['title'] = $clean_data['label'] = ( isset( $data['title'] ) ) ? apply_filters( 'title_save_pre', $data['title'] ) : '';
		$clean_data['arrows'] = ( isset( $data['arrows'] ) && 1 === (int) $data['arrows'] ) ? 1 : 0;
		$clean_data['dots'] = ( isset( $data['dots'] ) && 1 === (int) $data['dots'] ) ? 1 : 0;
		$clean_data['autoplay'] = ( isset( $data['autoplay'] ) && 1 === (int) $data['autoplay'] ) ? 1 : 0;

		if ( isset( $data['transition'] ) && in_array( $data['transition'], array( 'fade', 'scrollHorz', 'none' ) ) ) {
			$clean_data['transition'] = $data['transition'];
		}

		if ( isset( $data['delay'] ) ) {
			$clean_data['delay'] = absint( $data['delay'] );
		}

		if ( isset( $data['height'] ) ) {
			$clean_data['height'] = absint( $data['height'] );
		}

		if ( isset( $data['responsive'] ) ) {
			$clean_data['responsive'] = ttfmake_sanitize_section_choice( $data['responsive'], 'responsive', $data['section-type'] );
		}

		if ( isset( $data['background-image'] ) && '' !== $data['background-image'] ) {
			$clean_data['background-image'] = ttfmake_sanitize_image_id( $data['background-image'] );
		} else {
			$clean_data['background-image'] = '';
		}

		if ( isset( $data['darken'] ) && (int) $data['darken'] == 1 ) {
			$clean_data['darken'] = 1;
		} else {
			$clean_data['darken'] = 0;
		}

		if ( isset( $data['background-color'] ) ) {
			$clean_data['background-color'] = maybe_hash_hex_color( $data['background-color'] );
		}

		if ( isset( $data['background-style'] ) ) {
			$clean_data['background-style'] = ttfmake_sanitize_section_choice( $data['background-style'], 'background-style', $data['section-type'] );
		}

		if ( isset( $data['background-position'] ) ) {
			$clean_data['background-position'] = ttfmake_sanitize_section_choice( $data['background-position'], 'background-position', $data['section-type'] );
		}

		if ( isset( $data['banner-slides'] ) && is_array( $data['banner-slides'] ) ) {
			$clean_data['banner-slides'] = array();

			foreach ( $data['banner-slides'] as $s => $slide ) {
				// Handle legacy data layout
				$id = isset( $slide['id'] ) ? $slide['id']: $s;

				$clean_slide_data = array( 'id' => $id );

				if ( isset( $slide['content'] ) ) {
					$clean_slide_data['content'] = sanitize_post_field( 'post_content', $slide['content'], ( get_post() ) ? get_the_ID() : 0, 'db' );
				}

				if ( isset( $slide['background-color'] ) ) {
					$clean_slide_data['background-color'] = maybe_hash_hex_color( $slide['background-color'] );
				}

				$clean_slide_data['darken'] = ( isset( $slide['darken'] ) && 1 === (int) $slide['darken'] ) ? 1 : 0;

				if ( isset( $slide['background-image'] ) && '' !== $slide['background-image'] ) {
					$clean_slide_data['background-image'] = ttfmake_sanitize_image_id( $slide['background-image'] );
				} else {
					$clean_slide_data['background-image'] = '';
				}

				$clean_slide_data['alignment'] = ( isset( $slide['alignment'] ) && in_array( $slide['alignment'], array( 'none', 'left', 'right' ) ) ) ? $slide['alignment'] : 'none';

				if ( isset( $slide['state'] ) ) {
					$clean_slide_data['state'] = ( in_array( $slide['state'], array( 'open', 'closed' ) ) ) ? $slide['state'] : 'open';
				}

				array_push( $clean_data['banner-slides'], $clean_slide_data );
			}
		}

		return $clean_data;
	}

	public function admin_enqueue_scripts( $hook_suffix ) {
		// Only load resources if they are needed on the current page
		if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ) ) || ! ttfmake_post_type_supports_builder( get_post_type() ) ) {
			return;
		}

		/**
		 * Filter any available extensions for the Make builder JS.
		 *
		 * @since 1.8.11.
		 *
		 * @param array    $dependencies    The list of dependencies.
		 */
		$dependencies = apply_filters( 'make_builder_js_extensions', array(
			'ttfmake-builder', 'ttfmake-builder-overlay'
		) );

		wp_enqueue_script(
			'builder-section-banner',
			Make()->scripts()->get_js_directory_uri() . '/builder/sections/banner.js',
			$dependencies,
			TTFMAKE_VERSION,
			true
		);
	}

	public function print_templates() {
		global $hook_suffix, $typenow;

		// Only show when adding/editing pages
		if ( ! ttfmake_post_type_supports_builder( $typenow ) || ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ) )) {
			return;
		}

		$section_definitions = ttfmake_get_sections();
		set_query_var( 'ttfmake_section_data', $section_definitions[ 'banner' ] );
		?>
		<script type="text/template" id="tmpl-ttfmake-section-banner">
		<?php get_template_part( 'inc/builder/sections/banner/builder-template' ); ?>
		</script>
		<?php set_query_var( 'ttfmake_section_data', array() ); ?>
		<script type="text/template" id="tmpl-ttfmake-section-banner-slide">
		<?php get_template_part( 'inc/builder/sections/banner/builder-template', 'slide' ); ?>
		</script>
		<?php
	}
}
endif;
