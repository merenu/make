<?php
/**
 * @package Make
 */
global $post;

$banner_slides = $section_data['banner-slides'];
$is_slider = ( count( $banner_slides ) > 1 ) ? true : false;
$darken   = ( isset( $section_data[ 'darken' ] ) ) ? absint( $section_data[ 'darken' ] ) : 0;
?>

<section id="<?php echo esc_attr( Make()->section()->get_html_id( $section_data ) ); ?>" class="builder-section <?php echo esc_attr( Make()->section()->get_html_class( $section_data ) ); ?>" style="<?php echo esc_attr( Make()->section()->get_section_styles( $section_data ) ); ?>">
	<?php if ( '' !== $section_data['title'] ) : ?>
	<h3 class="builder-banner-section-title">
		<?php echo apply_filters( 'the_title', $section_data['title'] ); ?>
	</h3>
	<?php endif; ?>
	<div class="builder-section-content<?php echo ( $is_slider ) ? ' cycle-slideshow' : ''; ?>"<?php echo ( $is_slider ) ? Make()->section()->banner_get_slider_atts( $section_data ) : ''; ?>>
		<?php if ( ! empty( $banner_slides ) ) : foreach ( $banner_slides as $slide ) : ?>
		<div class="builder-banner-slide<?php echo Make()->section()->get_banner_slide_class( $slide ); ?>" style="<?php echo Make()->section()->get_banner_slide_style( $slide, $section_data ); ?>">
			<div class="builder-banner-content">
				<div class="builder-banner-inner-content">
					<?php Make()->section()->get_content( $slide['content'] ); ?>
				</div>
			</div>
			<?php if ( 0 !== absint( $slide['darken'] ) ) : ?>
			<div class="builder-banner-overlay"></div>
			<?php endif; ?>
		</div>
		<?php endforeach; endif; ?>
		<?php if ( $is_slider && true === (bool) $section_data['arrows'] ) : ?>
		<div class="cycle-prev"></div>
		<div class="cycle-next"></div>
		<?php endif; ?>
		<?php if ( $is_slider && true === (bool) $section_data['dots'] ) : ?>
		<div class="cycle-pager"></div>
		<?php endif; ?>
	</div>
	<?php if ( 0 !== $darken ) : ?>
	<div class="builder-section-overlay"></div>
	<?php endif; ?>
</section>
