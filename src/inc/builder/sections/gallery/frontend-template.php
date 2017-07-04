<?php
/**
 * @package Make
 */

global $post;

$gallery  = $section_data['gallery-items'];
$darken   = ( isset( $section_data[ 'darken' ] ) ) ? absint( $section_data[ 'darken' ] ) : 0;
$captions = ( isset( $section_data[ 'captions' ] ) ) ? esc_attr( $section_data[ 'captions' ] ) : 'reveal';
$aspect   = ( isset( $section_data[ 'aspect' ] ) ) ? esc_attr( $section_data[ 'aspect' ] ) : 'square';
?>

<section id="<?php echo esc_attr( Make()->section()->get_html_id( $section_data ) ); ?>" class="builder-section<?php echo esc_attr( Make()->section()->get_html_class( $section_data ) ); ?>" style="<?php echo Make()->section()->get_section_styles( $section_data ); ?>">
	<?php if ( '' !== $section_data['title'] ) : ?>
	<h3 class="builder-gallery-section-title">
		<?php echo apply_filters( 'the_title', $section_data['title'] ); ?>
	</h3>
	<?php endif; ?>
	<div class="builder-section-content">
		<?php if ( ! empty( $gallery ) ) : $i = 0; foreach ( $gallery as $item ) : $i++; ?>
		<div class="builder-gallery-item<?php echo esc_attr( Make()->section()->get_gallery_item_class( $item, $section_data, $i ) ); ?>"<?php echo Make()->section()->get_gallery_item_onclick( $item['link'], $section_data, $i ); ?>>
			<?php $image = Make()->section()->get_gallery_item_image( $item, $aspect, $section_data ); ?>
			<?php if ( '' !== $image ) : ?>
				<?php echo $image; ?>
			<?php endif; ?>
			<?php if ( 'none' !== $captions && ( '' !== $item['title'] || '' !== $item['description'] || has_excerpt( $item['background-image'] ) ) ) : ?>
			<div class="builder-gallery-content">
				<div class="builder-gallery-content-inner">
					<?php if ( '' !== $item['title'] ) : ?>
					<h4 class="builder-gallery-title">
						<?php echo apply_filters( 'the_title', $item['title'] ); ?>
					</h4>
					<?php endif; ?>
					<?php if ( '' !== $item['description'] ) : ?>
					<div class="builder-gallery-description">
						<?php Make()->section()->get_content( $item['description'] ); ?>
					</div>
					<?php elseif ( has_excerpt( $item['background-image'] ) ) : ?>
					<div class="builder-gallery-description">
						<?php echo Make()->sanitize()->sanitize_text( get_post( $item['background-image'] )->post_excerpt ); ?>
					</div>
					<?php endif; ?>
				</div>
			</div>
			<?php endif; ?>
		</div>
		<?php endforeach; endif; ?>
	</div>
	<?php if ( 0 !== $darken ) : ?>
	<div class="builder-section-overlay"></div>
	<?php endif; ?>
</section>
