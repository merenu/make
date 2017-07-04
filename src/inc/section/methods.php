<?php
/**
 * @package Make
 */

/**
 * Class MAKE_Plus_Methods
 *
 * @since 1.7.0.
 */
class MAKE_Section_Methods extends MAKE_Util_Modules implements MAKE_Section_MethodsInterface, MAKE_Util_HookInterface {
	/**
	 * Whether Make Plus is installed and active.
	 *
	 * @since 1.7.0.
	 *
	 * @var bool
	 */
	private $plus = null;

	/**
	 * Indicator of whether the hook routine has been run.
	 *
	 * @since 1.7.0.
	 *
	 * @var bool
	 */
	private static $hooked = false;

	protected $dependencies = array(
		'scripts' => 'MAKE_Setup_ScriptsInterface',
	);

	/**
	 * Hook into WordPress.
	 *
	 * @since 1.7.0.
	 *
	 * @return void
	 */
	public function hook() {
		if ( $this->is_hooked() ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_builder_scripts' ) );
		add_action( 'make_builder_banner_css', array( $this, 'builder_banner_styles' ), 10, 3 );
		add_action( 'make_style_loaded', array( $this, 'builder_styles' ) );

		// Hooking has occurred.
		self::$hooked = true;
	}

	/**
	 * Check if the hook routine has been run.
	 *
	 * @since 1.7.0.
	 *
	 * @return bool
	 */
	public function is_hooked() {
		return self::$hooked;
	}

	public function load_section_template( $slug, $path, $return = false, $section_data ) {
		$templates = array(
			$slug . '.php',
			trailingslashit( $path ) . $slug . '.php'
		);

		/**
		 * Filter the templates to try and load.
		 *
		 * @since 1.2.3.
		 *
		 * @param array    $templates    The list of template to try and load.
		 * @param string   $slug         The template slug.
		 * @param string   $path         The path to the template.
		 */
		$templates = apply_filters( 'make_load_section_template', $templates, $slug, $path );

		if ( '' === $located = locate_template( $templates, true, false ) ) {
			if ( isset( $templates[1] ) && file_exists( $templates[1] ) ) {
				if ( $return ) {
					ob_start();
				}

				require( $templates[1] );
				$located = $templates[1];

				if ( $return ) {
					$located = ob_get_clean();
				}
			}
		}

		return $located;
	}

	public function get_html_id( $current_section ) {
		$prefix = 'builder-section-';
		$id = sanitize_title_with_dashes( $current_section['id'] );

		/**
		 * Filter the section wrapper's HTML id attribute.
		 *
		 * @since 1.6.0.
		 *
		 * @param string    $section_id         The string used in the section's HTML id attribute.
		 * @param array     $current_section    The data for the section.
		 */
		return apply_filters( 'make_section_html_id', $prefix . $id, $current_section );
	}

	public function get_sections( $sections_meta ) {
		$sections = array();

		if ( ! empty( $sections_meta ) ) {
			foreach ( $sections_meta as $section ) {
				$section_meta = get_metadata_by_mid( 'post', $section );
				$section_data = json_decode( wp_unslash( $section_meta->meta_value ), true );

				if ( isset( $section_data['master_id'] ) && !empty( $section_data['master_id'] ) ) {
					$master_option = get_option( $section_data['master_id'] );
					$master_section_data = json_decode( wp_unslash( $master_option ), true );
					$master_section_data['id'] = $section_data['id'];

					$section_data = $master_section_data;
				}

				$sections[$section_data['id']] = $section_data;
			}
		}

		return $sections;
	}

	public function get_prev_section_data( $current_section, $sections ) {
		foreach ( $sections as $sid => $data ) {
			if ( $current_section['id'] == $data['id'] ) {
				break;
			} else {
				$prev_key = $sid;
			}
		}

		$prev_section = ( isset( $prev_key ) && isset( $sections[ $prev_key ] ) ) ? $sections[ $prev_key ] : array();

		/**
		 * Allow developers to alter the "next" section data.
		 *
		 * @since 1.2.3.
		 *
		 * @param array    $prev_section       The data for the next section.
		 * @param array    $current_section    The data for the current section.
		 * @param array    $sections           The list of all sections.
		 */
		return apply_filters( 'make_get_prev_section_data', $prev_section, $current_section, $sections );
	}

	public function get_next_section_data( $current_section, $sections ) {
		$next_is_the_one = false;
		$next_data       = array();

		if ( ! empty( $sections ) ) {
			foreach ( $sections as $sid => $data ) {
				if ( true === $next_is_the_one ) {
					$next_data = $data;
					break;
				}

				if ( $current_section['id'] == $data['id'] ) {
					$next_is_the_one = true;
				}
			}
		}

		/**
		 * Allow developers to alter the "next" section data.
		 *
		 * @since 1.2.3.
		 *
		 * @param array    $next_data          The data for the next section.
		 * @param array    $current_section    The data for the current section.
		 * @param array    $sections           The list of all sections.
		 */
		return apply_filters( 'make_get_next_section_data', $next_data, $current_section, $sections );
	}

	public function get_html_class( $section_data ) {
		$section_type = $section_data['section-type'];

		global $post;
		$sections_meta = json_decode( wp_unslash( get_post_meta( $post->ID, '__ttfmake_layout', true ) ), true );
		$sections = $this->get_sections( $sections_meta );

		$prefix = 'builder-section-';

		// Get the current section type
		$current = ( $section_type ) ? $prefix . $section_type : '';

		$next_data = $this->get_next_section_data( $section_data, $sections );
		$next = ( ! empty( $next_data ) && isset( $next_data['section-type'] ) ) ? $prefix . 'next-' . $next_data['section-type'] : $prefix . 'last';

		// Get the previous section's type
		$prev_data = $this->get_prev_section_data( $section_data, $sections );
		$prev      = ( ! empty( $prev_data ) && isset( $prev_data['section-type'] ) ) ? $prefix . 'prev-' . $prev_data['section-type'] : $prefix . 'first';

		/**
		 * Filter the section classes.
		 *
		 * @since 1.2.3.
		 *
		 * @param string    $classes            The sting of classes.
		 * @param array     $current_section    The array of data for the current section.
		 */
		$section_classes = apply_filters( 'make_section_classes', $prev . ' ' . $current . ' ' . $next, $section_data );

		$html_class = ' ';

		$full_width = isset( $section_data['full-width'] ) && 0 !== absint( $section_data['full-width'] );

		if ( true === $full_width ) {
			$html_class .= ' builder-section-full-width';
		}

		$bg_color = ( isset( $section_data['background-color'] ) && ! empty( $section_data['background-color'] ) );

		$bg_image = ( isset( $section_data['background-image'] ) && 0 !== absint( $section_data['background-image'] ) );
		if ( true === $bg_color || true === $bg_image ) {
			$html_class .= ' has-background';
		}

		switch( $section_type ) {
			case 'text':
				$columns_number = ( isset( $section_data['columns-number'] ) ) ? absint( $section_data['columns-number'] ) : 1;
				$html_class .= ' builder-text-columns-' . $columns_number;

				/**
				 * Filter the text section class.
				 *
				 * @since 1.2.3.
				 *
				 * @param string    $text_class              The computed class string.
				 * @param array     $ttfmake_section_data    The section data.
				 * @param array     $sections                The list of sections.
				 */
				$section_specific_classes = apply_filters( 'make_builder_get_text_class', $html_class, $section_data, $sections );
				break;

			case 'gallery':
				$gallery_columns = ( isset( $section_data['columns'] ) ) ? absint( $section_data['columns'] ) : 1;
				$html_class  .= ' builder-gallery-columns-' . $gallery_columns;

				// Captions
				if ( isset( $section_data['captions'] ) && ! empty( $section_data['captions'] ) ) {
					$html_class .= ' builder-gallery-captions-' . esc_attr( $section_data['captions'] );
				}

				// Caption color
				if ( isset( $section_data['caption-color'] ) && ! empty( $section_data['caption-color'] ) ) {
					$html_class .= ' builder-gallery-captions-' . esc_attr( $section_data['caption-color'] );
				}

				// Aspect Ratio
				if ( isset( $section_data['aspect'] ) && ! empty( $section_data['aspect'] ) ) {
					$html_class .= ' builder-gallery-aspect-' . esc_attr( $section_data['aspect'] );
				}

				/**
				 * Filter the class applied to a gallery.
				 *
				 * @since 1.2.3.
				 *
				 * @param string    $gallery_class           The class applied to the gallery.
				 * @param array     $ttfmake_section_data    The section data.
				 * @param array     $sections                The list of sections.
				 */
				return apply_filters( 'make_gallery_class', $html_class, $section_data, $sections );
				break;

			case 'banner':
				/**
				 * Filter the class for the banner section.
				 *
				 * @since 1.2.3.
				 *
				 * @param string    $banner_class            The banner class.
				 * @param array     $ttfmake_section_data    The section data.
				 */
				$section_specific_classes = apply_filters( 'make_builder_banner_class', $html_class, $section_data );
				break;
		}

		if ( !isset( $section_specific_classes ) ) {
			global $section_specific_classes;
		}

		return $section_classes . $section_specific_classes;
	}

	public function get_section_styles( $section_data ) {
		$style = '';

		// Background color
		if ( isset( $section_data['background-color'] ) && ! empty( $section_data['background-color'] ) ) {
			$style .= 'background-color:' . maybe_hash_hex_color( $section_data['background-color'] ) . ';';
		}

		// Background image
		if ( isset( $section_data['background-image'] ) && 0 !== absint( $section_data['background-image'] ) ) {
			$image_src = ttfmake_get_image_src( $section_data['background-image'], 'full' );
			if ( isset( $image_src[0] ) ) {
				$style .= 'background-image: url(\'' . addcslashes( esc_url_raw( $image_src[0] ), '"' ) . '\');';
			}
		}

		// Background style
		if ( isset( $section_data['background-style'] ) && ! empty( $section_data['background-style'] ) ) {
			if ( in_array( $section_data['background-style'], array( 'cover', 'contain' ) ) ) {
				$style .= 'background-size: ' . $section_data['background-style'] . '; background-repeat: no-repeat;';
			}
		}

		// Background position
		if ( isset( $section_data['background-position'] ) && ! empty( $section_data['background-position'] ) ) {
			$rule = explode( '-', $section_data['background-position'] );
			$style .= 'background-position: ' . implode( ' ', $rule ) . ';';
		}

		/**
		 * Filter the style added to a gallery section.
		 *
		 * @since 1.2.3.
		 *
		 * @param string    $gallery_style           The style applied to the gallery.
		 * @param array     $ttfmake_section_data    The section data.
		 */
		return apply_filters( 'make_builder_get_' . $section_data['section-type'] . '_style', $style, $section_data );
	}

	public function get_content( $content ) {
		/**
		 * Filter the content used for "post_content" when the builder is used to generate content.
		 *
		 * @since 1.2.3.
		 * @deprecated 1.7.0.
		 *
		 * @param string    $content    The post content.
		 */
		$content = apply_filters( 'ttfmake_the_builder_content', $content );

		/**
		 * Filter the content used for "post_content" when the builder is used to generate content.
		 *
		 * @since 1.2.3.
		 *
		 * @param string    $content    The post content.
		 */
		$content = apply_filters( 'make_the_builder_content', $content );

		$content = str_replace( ']]>', ']]&gt;', $content );

		echo $content;
	}

	public function is_section_type( $type, $data ) {
		$is_section_type = ( isset( $data['section-type'] ) && $type === $data['section-type'] );

		/**
		 * Allow developers to alter if a set of data is a specified section type.
		 *
		 * @since 1.2.3.
		 *
		 * @param bool      $is_section_type    Whether or not the data represents a specific section.
		 * @param string    $type               The section type to check.
		 * @param array     $data               The section data.
		 */
		return apply_filters( 'make_builder_is_section_type', $is_section_type, $type, $data );
	}

	public function banner_get_slider_atts( $section_data ) {
		$data_attributes = '';

		if ( $this->is_section_type( 'banner', $section_data ) ) {
			$atts = shortcode_atts( array(
				'autoplay'   => true,
				'transition' => 'scrollHorz',
				'delay'      => 6000
			), $section_data );

			// Data attributes
			$data_attributes  = ' data-cycle-log="false"';
			$data_attributes .= ' data-cycle-slides="div.builder-banner-slide"';
			$data_attributes .= ' data-cycle-swipe="true"';

			// Autoplay
			$autoplay = (bool) $atts['autoplay'];
			if ( false === $autoplay ) {
				$data_attributes .= ' data-cycle-paused="true"';
			}

			// Delay
			$delay = absint( $atts['delay'] );
			if ( 0 === $delay ) {
				$delay = 6000;
			}

			if ( 4000 !== $delay ) {
				$data_attributes .= ' data-cycle-timeout="' . esc_attr( $delay ) . '"';
			}

			// Effect
			$effect = trim( $atts['transition'] );
			if ( ! in_array( $effect, array( 'fade', 'fadeout', 'scrollHorz', 'none' ) ) ) {
				$effect = 'scrollHorz';
			}

			if ( 'fade' !== $effect ) {
				$data_attributes .= ' data-cycle-fx="' . esc_attr( $effect ) . '"';
			}
		}

		return $data_attributes;
	}

	public function get_banner_slide_class( $slide ) {
		$slide_class = '';

		// Content position
		if ( isset( $slide['alignment'] ) && '' !== $slide['alignment'] ) {
			$slide_class .= ' ' . sanitize_html_class( 'content-position-' . $slide['alignment'] );
		}

		/**
		 * Allow developers to alter the class for the banner slide.
		 *
		 * @since 1.2.3.
		 *
		 * @param string $slide_class The banner classes.
		 */
		return apply_filters( 'make_builder_banner_slide_class', $slide_class, $slide );
	}

	public function get_banner_slide_style( $slide, $section_data ) {
		$slide_style = '';

		// Background color
		if ( isset( $slide['background-color'] ) && '' !== $slide['background-color'] ) {
			$slide_style .= 'background-color:' . maybe_hash_hex_color( $slide['background-color'] ) . ';';
		}

		// Background image
		if ( isset( $slide['background-image'] ) && 0 !== $this->sanitize_image_id( $slide['background-image'] ) ) {
			$image_src = $this->get_image_src( $slide['background-image'], 'full' );
			if ( isset( $image_src[0] ) ) {
				$slide_style .= 'background-image: url(\'' . addcslashes( esc_url_raw( $image_src[0] ), '"' ) . '\');';
			}
		}

		/**
		 * Allow developers to change the CSS for a Banner section.
		 *
		 * @since 1.2.3.
		 *
		 * @param string    $slide_style             The CSS for the banner.
		 * @param array     $slide                   The slide data.
		 * @param array     $ttfmake_section_data    The section data.
		 */
		return apply_filters( 'make_builder_banner_slide_style', esc_attr( $slide_style ), $slide, $section_data );
	}

	public function sanitize_image_id( $id ) {
		if ( false !== strpos( $id, 'x' ) ) {
			$pieces       = explode( 'x', $id );
			$clean_pieces = array_map( 'absint', $pieces );
			$id           = implode( 'x', $clean_pieces );
		} else {
			$id = absint( $id );
		}

		return $id;
	}

	public function get_image_src( $image_id, $size ) {
		$src = '';

		if ( false === strpos( $image_id, 'x' ) ) {
			$image = wp_get_attachment_image_src( $image_id, $size );

			if ( false !== $image && isset( $image[0] ) ) {
				$src = $image;
			}
		} else {
			$image = $this->get_placeholder_image( $image_id );

			if ( isset( $image['src'] ) ) {
				$wp_src = array(
					0 => $image['src'],
					1 => $image['width'],
					2 => $image['height'],
				);
				$src = array_merge( $image, $wp_src );
			}
		}

		/**
		 * Filter the image source attributes.
		 *
		 * @since 1.2.3.
		 *
		 * @param string    $src         The image source attributes.
		 * @param int       $image_id    The ID for the image.
		 * @param bool      $size        The requested image size.
		 */
		return apply_filters( 'make_get_image_src', $src, $image_id, $size );
	}

	function get_placeholder_image( $image_id ) {
		global $ttfmake_placeholder_images;
		$return = array();

		if ( isset( $ttfmake_placeholder_images[ $image_id ] ) ) {
			$return = $ttfmake_placeholder_images[ $image_id ];
		}

		/**
		 * Filter the image source attributes.
		 *
		 * @since 1.2.3.
		 *
		 * @param string    $return                        The image source attributes.
		 * @param int       $image_id                      The ID for the image.
		 * @param bool      $ttfmake_placeholder_images    The list of placeholder images.
		 */
		return apply_filters( 'make_get_placeholder_image', $return, $image_id, $ttfmake_placeholder_images );
	}

	/**
	 * Trigger an action hook for each section on a Builder page for the purpose
	 * of adding section-specific CSS rules to the document head.
	 *
	 * @since 1.4.5
	 *
	 * @hooked action make_style_loaded
	 *
	 * @param MAKE_Style_ManagerInterface $style    The style manager instance.
	 *
	 * @return void
	 */
	public function builder_styles( MAKE_Style_ManagerInterface $style ) {
		if ( ttfmake_is_builder_page() ) {
			$sections_meta = json_decode( wp_unslash( get_post_meta( get_the_ID(), '__ttfmake_layout', true ) ), true );
			$sections = $this->get_sections( $sections_meta );

			if ( ! empty( $sections ) ) {
				foreach ( $sections as $id => $data ) {
					if ( isset( $data['section-type'] ) ) {
						/**
						 * Allow section-specific CSS rules to be added to the document head of a Builder page.
						 *
						 * @since 1.4.5
						 * @since 1.7.0. Added the $style parameter.
						 *
						 * @param array                       $data     The Builder section's data.
						 * @param int                         $id       The ID of the Builder section.
						 * @param MAKE_Style_ManagerInterface $style    The style manager instance.
						 */

						do_action( 'make_builder_' . $data['section-type'] . '_css', $data, $data['id'], $style );
					}
				}
			}
		}
	}

	public function builder_banner_styles( array $data, $id, MAKE_Style_ManagerInterface $style ) {
		$prefix = 'builder-section-';
		$id = sanitize_title_with_dashes( $data['id'] );
		/**
		 * This filter is documented in inc/builder/core/save.php
		 */
		$section_id = apply_filters( 'make_section_html_id', $prefix . $id, $data );

		$responsive = ( isset( $data['responsive'] ) ) ? $data['responsive'] : 'balanced';
		$slider_height = absint( $data['height'] );
		if ( 0 === $slider_height ) {
			$slider_height = 600;
		}
		$slider_ratio = ( $slider_height / 960 ) * 100;

		if ( 'aspect' === $responsive ) {
			$style->css()->add( array(
				'selectors'    => array( '#' . esc_attr( $section_id ) . ' .builder-banner-slide' ),
				'declarations' => array(
					'padding-bottom' => $slider_ratio . '%'
				),
			) );
		} else {
			$style->css()->add( array(
				'selectors'    => array( '#' . esc_attr( $section_id ) . ' .builder-banner-slide' ),
				'declarations' => array(
					'padding-bottom' => $slider_height . 'px'
				),
			) );
			$style->css()->add( array(
				'selectors'    => array( '#' . esc_attr( $section_id ) . ' .builder-banner-slide' ),
				'declarations' => array(
					'padding-bottom' => $slider_ratio . '%'
				),
				'media'        => 'screen and (min-width: 600px) and (max-width: 960px)'
			) );
		}
	}

	public function frontend_builder_scripts() {
		if ( ttfmake_is_builder_page() ) {
			$sections_meta = json_decode( wp_unslash( get_post_meta( get_the_ID(), '__ttfmake_layout', true ) ), true );
			$sections = $this->get_sections( $sections_meta );

			// Bail if there are no sections
			if ( empty( $sections ) ) {
				return;
			}

			// Parse the sections included on the page.
			$section_types = wp_list_pluck( $sections, 'section-type' );

			foreach ( $section_types as $section_id => $section_type ) {
				switch ( $section_type ) {
					default :
						break;
					case 'banner' :
					case 'postlist' :
					case 'productgrid' :
						// Add Cycle2 as a dependency for the Frontend script
						$this->scripts()->add_dependency( 'make-frontend', 'cycle2', 'script' );
						if ( defined( 'SCRIPT_DEBUG' ) && true === SCRIPT_DEBUG ) {
							$this->scripts()->add_dependency( 'make-frontend', 'cycle2-center', 'script' );
							$this->scripts()->add_dependency( 'make-frontend', 'cycle2-swipe', 'script' );
						}
						break;
				}
			}
		}
	}

	public function get_gallery_item_class( $item, $section_data, $i ) {
		$gallery_class = '';

		// Link
		$has_link = ( isset( $item['link'] ) && ! empty( $item['link'] ) ) ? true : false;
		if ( true === $has_link ) {
			$gallery_class .= ' has-link';
		}

		// Columns
		$gallery_columns = ( isset( $section_data['columns'] ) ) ? absint( $section_data['columns'] ) : 1;
		if ( $gallery_columns > 2 && 0 === $i % $gallery_columns ) {
			$gallery_class .= ' last-' . $gallery_columns;
		}

		if ( 0 === $i % 2 ) {
			$gallery_class .= ' last-2';
		}

		/**
		 * Filter the class used for a gallery item.
		 *
		 * @since 1.2.3.
		 *
		 * @param string    $gallery_class           The computed gallery class.
		 * @param array     $item                    The item's data.
		 * @param array     $section_data    The section data.
		 * @param int       $i                       The current gallery item number.
		 */
		return apply_filters( 'make_builder_get_gallery_item_class', $gallery_class, $item, $section_data, $i );
	}

	public function get_gallery_item_onclick( $link, $section_data, $i ) {
		if ( '' === $link ) {
			return '';
		}

		$item = $ttfmake_section_data['gallery-items'][$i - 1];
		$external = isset( $item['open-new-tab'] ) && $item['open-new-tab'] === 1;
		$url = esc_js( esc_url( $link ) );
		$open_function = $external ? 'window.open(\'' . $url . '\')': 'window.location.href = \'' . $url . '\'';
		$onclick = ' onclick="event.preventDefault(); '. $open_function . ';"';

		/**
		 * Filter the class used for a gallery item.
		 *
		 * @since 1.7.6.
		 *
		 * @param string    $onclick                 The computed gallery onclick attribute.
		 * @param string    $link                    The item's link.
		 * @param array     $ttfmake_section_data    The section data.
		 * @param int       $i                       The current gallery item number.
		 */
		return apply_filters( 'make_builder_get_gallery_item_onclick', $onclick, $link, $section_data, $i );
	}

	public function get_gallery_item_image( $item, $aspect, $section_data ) {
		$image = '';

		if ( $this->is_section_type( 'gallery', $section_data ) && 0 !== $this->sanitize_image_id( $item[ 'background-image' ] ) ) {
			$image_style = '';

			$image_src = $this->get_image_src( $item[ 'background-image' ], 'large' );
			if ( isset( $image_src[0] ) ) {
				$image_style .= 'background-image: url(\'' . addcslashes( esc_url_raw( $image_src[0] ), '"' ) . '\');';
			}

			if ( 'none' === $aspect && isset( $image_src[1] ) && isset( $image_src[2] ) ) {
				$image_ratio = ( $image_src[2] / $image_src[1] ) * 100;
				$image_style .= 'padding-bottom: ' . $image_ratio . '%;';
			}

			if ( '' !== $image_style ) {
				$image .= '<figure class="builder-gallery-image" style="' . esc_attr( $image_style ) . '"></figure>';
			}
		}

		/**
		 * Alter the generated gallery image.
		 *
		 * @since 1.2.3.
		 *
		 * @param string    $image     The image HTML.
		 * @param array     $item      The item's data.
		 * @param string    $aspect    The aspect ratio for the section.
		 */
		return apply_filters( 'make_builder_get_gallery_item_image', $image, $item, $aspect );
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
	 * Return the default value for a particular section setting.
	 *
	 * @since 1.0.4.
	 *
	 * @param  string    $key             The key for the section setting.
	 * @param  string    $section_type    The section type.
	 * @return mixed                      Default value if found; false if not found.
	 */
	public function get_section_default( $key, $section_type ) {
		$defaults = $this->get_section_defaults();
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
	public function sanitize_section_choice( $value, $key, $section_type ) {
		$choices         = $this->get_section_choices( $key, $section_type );
		$allowed_choices = array_keys( $choices );

		if ( ! in_array( $value, $allowed_choices ) ) {
			$value = $this->get_section_default( $key, $section_type );
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

	/**
	 * Define the choices for section setting dropdowns.
	 *
	 * @since  1.0.4.
	 *
	 * @param  string    $key             The key for the section setting.
	 * @param  string    $section_type    The section type.
 	 * @return array                      The array of choices for the section setting.
	 */
	public function get_section_choices( $key, $section_type ) {
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
}
