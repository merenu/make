<?php
/**
 * @package Make
 */

ttfmake_load_section_header();

global $ttfmake_section_data;
?>

<div class="ttfmake-gallery-items">
	<div class="ttfmake-gallery-items-stage ttfmake-gallery-columns-{{ data.get('columns') }}"></div>
	<div class="ttfmake-add-item-wrapper">
		<a href="#" class="ttfmake-add-item ttfmake-gallery-add-item-link" title="<?php esc_attr_e( 'Add new item', 'make' ); ?>">
			<span>
				<?php esc_html_e( 'Add new item', 'make' ); ?>
			</span>
		</a>
	</div>
</div>

<?php ttfmake_load_section_footer();