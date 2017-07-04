<?php
/**
 * @package Make
 */

if ( ! class_exists( 'TTFMAKE_Section_Instances' ) ) :
/**
 * Handler for section overlays
 *
 * @since 1.9.0.
 *
 * Class TTFMAKE_Section_Instances
 */
class TTFMAKE_Section_Instances {

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
		add_filter( 'make_section_settings', array( $this, 'section_settings' ), 70, 2 );
		add_filter( 'make_sections_defaults', array( $this, 'add_section_defaults' ), 999 );
		add_filter( 'make_prepare_data_section', array( $this, 'save_section' ), 20, 2 );
		//add_filter( 'make_prepare_data_section', array( $this, 'save_sid_field' ), 10, 2 );
		add_filter( 'make_builder_data_saved', array( $this, 'save_layout' ), 10, 2 );
		add_filter( 'make_get_section_data', array( $this, 'read_layout' ), 10, 2 );
		add_filter( 'make_get_section_json', array( $this, 'remove_parent_ids' ), 100, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}

	public function section_settings( $settings, $section_type ) {
		if ( ! in_array( $section_type, array(
			'text', 'banner', 'gallery', 'panels', 'postlist', 'productgrid', 'downloads'
			) ) ) {
			return $settings;
		}

		$index = max( array_keys( $settings ) );

		$settings[$index + 100] = array(
			'type'    => 'divider',
			'label'   => __( 'Master', 'make-plus' ),
			'name'    => 'divider-master',
			'class'   => 'ttfmake-configuration-divider',
		);

		$settings[$index + 125] = array(
			'type'    => 'checkbox',
			'label'   => __( 'Master', 'make-plus' ),
			'name'    => 'master',
			'default' => 0,
		);

		$master_sections = $this->get_master_sections( false, $section_type );
		$default_option = array( '' => __( 'Create new master', 'make-plus' ) );
		$options = array_combine( array_keys( $master_sections ), array_keys( $master_sections ) );

		$settings[$index + 150] = array(
			'type'    => 'select',
			'label'   => __( 'Master ID', 'make-plus' ),
			'name'    => 'master_id',
			'default' => '',
			'options' => $default_option + $options,
			'disabled' => true,
		);

		return $settings;
	}

	public function add_section_defaults( $defaults ) {
		foreach ( $defaults as $section_id => $section_defaults ) {
			$defaults[ $section_id ][ 'master' ] = 0;
			$defaults[ $section_id ][ 'master_id' ] = '';
		}

		return $defaults;
	}

	public function save_section( $clean_data, $raw_data ) {
		$clean_data[ 'sid' ] = $raw_data[ 'sid' ];

		if ( isset( $raw_data[ 'master' ] ) ) {
			if ( $raw_data[ 'master' ] == 1 ) {
				$clean_data[ 'master' ] = 1;

				if ( ! $raw_data[ 'master_id' ] || empty( $raw_data['master_id'] ) ) {
					$option_id = $this->generate_unique_master_name( $section[ 'section-type' ] );
					// Set the master_id reference on the instance
					$raw_data[ 'master_id' ] = $option_id;
				} else {
					$option_id = $section[ 'master_id' ];
				}

				$clean_data[ 'master_id' ] = $raw_data[ 'master_id' ];
			} else {
				$clean_data[ 'master' ] = 0;
				$clean_data[ 'master_id' ] = 0;
			}
		}

		return $clean_data;
	}

	public function save_layout( $sections, $post_id ) {
		// Skip if this is being run on a revision
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Remove legacy post meta
		delete_post_meta( $post_id, '_ttfmake-section-ids' );

		$post_meta = get_post_meta( $post_id );

		foreach ( $post_meta as $key => $value ) {
			if ( 0 === strpos( $key, '_ttfmake:' ) ) {
				delete_post_meta( $post_id, $key );
			}
		}

		$layout = array();

		foreach( $sections as $id => $section ) {
			// If it's the first time we store this section
			// append the sid value coming from db
			if ( ! isset( $section[ 'sid' ] ) || ! $section[ 'sid' ] ) {
				$section_id = add_post_meta( $post_id, '__ttfmake_section', '', true );
				$section[ 'sid' ] = "{$section_id}";
			}

			if ( $section[ 'master' ] ) {
				// If the section is set as master, and no reference
				// to an existing master is set, create the master
				// section entry in wp_options table
				if ( ! $section[ 'master_id' ] ) {
					$option_id = $this->generate_unique_master_name( $section[ 'section-type' ] );
					// Set the master_id reference on the instance
					$section[ 'master_id' ] = $option_id;
				} else {
					$option_id = $section[ 'master_id' ];
				}

				// These keys should be removed from the master,
				// and be the only keys remaining on the instance.
				$id_keys = array( 'id', 'sid', 'master', 'master_id' );
				$master = array_diff_key( $section, array_flip( $id_keys ) );
				$section = array_intersect_key( $section, array_flip( $id_keys ) );
				// Update the master
				update_option( $option_id, wp_slash( json_encode( $master ) ) );
			} else if ( ! $section[ 'master' ] ) {
				// Clear the master_id reference
				// if section isn't master anymore
				$section[ 'master_id' ] = ttfmake_get_section_default( 'master_id', $section[ 'section-type' ] );
			}

			// Convert section virtual ID to string
			// to avoid JSON decode integer overflows
			$section[ 'id' ] = strval( $section[ 'id' ] );

			// Avoid adding new metadatas each time.
			// Instead, update the section meta
			// using the meta id
			update_metadata_by_mid(
				'post',
				$section[ 'sid' ],
				wp_slash( json_encode( $section ) ),
				'__ttfmake_section_' . $section[ 'sid' ],
				true
			);

			$layout[] = $section[ 'sid' ];
		}

		// Purge removed sections
		$current_layout_meta = get_post_meta( $post_id, '__ttfmake_layout', true );

		if ( $current_layout_meta ) {
			$current_layout = json_decode( wp_unslash( $current_layout_meta ), true );
			$removed_section_ids = array_diff( $current_layout, $layout );

			foreach ( $removed_section_ids as $section_id ) {
				delete_post_meta( $post_id, "__ttfmake_section_{$section_id}" );
			}
		}

		// Update layout
		update_post_meta( $post_id, '__ttfmake_layout', wp_slash( json_encode( $layout ) ) );
	}

	public function read_layout( $sections, $post_id ) {
		$layout_meta = get_post_meta( $post_id, '__ttfmake_layout', true );

		if ( $layout_meta ) {
			$layout = json_decode( wp_unslash( $layout_meta ), true );
			$sections = array();

			foreach ( $layout as $section_id ) {
				$section = $this->read_section( $section_id );
				$sections[] = $section;
			}
		}

		return $sections;
	}

	public function read_section( $section_id ) {
		// Fetch section using its db id
		$section_meta = get_metadata_by_mid( 'post', $section_id );
		$section = json_decode( wp_unslash( $section_meta->meta_value ), true );

		if ( $section[ 'master_id' ] ) {
			$master_meta = get_option( $section[ 'master_id' ] );
			$master = json_decode( wp_unslash( $master_meta ), true );
			// Merge the master data with the section instance
			$section += $master;
		}

		return $section;
	}

	public function remove_parent_ids( $data ) {
		/*
		 * Remove parentIDs from section items
		 *
		 * @since 1.8.11.
		 */

		// Banners
		if ( isset( $data['banner-slides'] ) && is_array( $data['banner-slides'] ) ) {
			foreach ( $data['banner-slides'] as $s => $slide ) {
				if ( isset( $slide['parentID'] ) ) {
					unset( $data['banner-slides'][$s]['parentID'] );
				}
			}
		}

		// Columns
		if ( isset( $data['columns'] ) && is_array( $data['columns'] ) ) {
			foreach ( $data['columns'] as $c => $column ) {
				if ( isset( $column['parentID'] ) ) {
					unset( $data['columns'][$c]['parentID'] );
				}
			}
		}

		// Gallery
		if ( isset( $data['gallery-items'] ) && is_array( $data['gallery-items'] ) ) {
			foreach ( $data['gallery-items'] as $g => $item ) {
				if ( isset( $item['parentID'] ) ) {
					unset( $data['gallery-items'][$g]['parentID'] );
				}
			}
		}

		return $data;
	}

	public function generate_unique_master_name( $section_type ) {
		$master_names = $this->get_master_sections( true );
		$master_slug = "ttfmake_master_{$section_type}";
		$existing_masters = preg_grep( '/^' . $master_slug . '/', $master_names );
		$suffix = 0;

		foreach ( $existing_masters as $master_name ) {
			// Find the highest numbered master name
			// with the specified section type in the slug
			preg_match( '/^' . $master_slug . '_+(\d+)/', $master_name, $suffix_match );
			$master_suffix = (int) $suffix_match[1];
			$suffix = $master_suffix > $suffix ? $master_suffix: $suffix;
		}

		// Increment the highest found suffix by 1
		$suffix ++;
		return "{$master_slug}_{$suffix}";
	}

	public function get_master_sections( $names_only = false, $section_type = false ) {
		$options = wp_load_alloptions();
		// Fetch all options prefixed with master prefix
		$master_ids = preg_grep( '/^ttfmake_master_/', array_keys( $options ) );

		// Filter found masters on section type
		if ( false !== $section_type ) {
			$master_ids = preg_grep( '/^ttfmake_master_' . $section_type . '_/', array_keys( $options ) );
		}

		// Return only master ids
		if ( true === $names_only ) {
			return $master_ids;
		}

		// Return full master data
		$masters = array();

		foreach ( $master_ids as $master_id ) {
			$master_data = $options[ $master_id ];
			$masters[ $master_id ] =  json_decode( wp_unslash( $master_data ), true );
		}

		return $masters;
	}

	public function admin_enqueue_scripts( $hook_suffix ) {
		// Only load resources if they are needed on the current page
		if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ) ) || ! ttfmake_post_type_supports_builder( get_post_type() ) ) {
			return;
		}

		wp_enqueue_script(
			'make-settings-overlay-master',
			get_stylesheet_directory_uri() . '/js/settings_master_select.js',
			array( 'make-settings-overlay' ),
			TTFMAKE_VERSION,
			true
		);

		// Section settings
		wp_localize_script(
			'make-settings-overlay-master',
			'masterSections',
			$this->get_master_sections()
		);
	}

}

endif;

if ( ! function_exists( 'ttfmake_get_section_instances' ) ) :
/**
 * Instantiate or return the one TTFMAKE_Section_Instances instance.
 *
 * @since  1.9.0.
 *
 * @return TTFMAKE_Section_Instances
 */
function ttfmake_get_section_instances() {
	return TTFMAKE_Section_Instances::instance();
}
endif;
