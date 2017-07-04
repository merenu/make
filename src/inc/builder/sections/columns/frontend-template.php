<?php
/**
 * @package Make
 */
 global $post;

 $darken   = ( isset( $section_data[ 'darken' ] ) ) ? absint( $section_data[ 'darken' ] ) : 0;
?>
<section id="<?php echo Make()->section()->get_html_id( $section_data ); ?>" class="builder-section <?php echo esc_attr( Make()->section()->get_html_class( $section_data ) ); ?>" style="<?php echo esc_attr( Make()->section()->get_section_styles( $section_data ) ); ?>">
	<?php if ( '' !== $section_data['title'] ) : ?>
	<h3 class="builder-text-section-title">
		<?php echo apply_filters( 'the_title', $section_data['title'] ); ?>
	</h3>
	<?php endif; ?>

	<div class="builder-section-content">
		<?php $text_columns = $section_data['columns']; ?>
		<?php $columns_layout_size = $section_data['columns-number']; ?>
		<?php if ( ! empty( $text_columns ) ) : $i = 1; foreach ( $text_columns as $column ) : ?>
			<?php if ( $i == 1 ): ?>
				<div class="builder-text-row">
			<?php endif; ?>
			<div class="builder-text-column builder-text-column-<?php echo $i; ?>" id="builder-section-<?php echo esc_attr( $section_data['id'] ); ?>-column-<?php echo $i; ?>">
				<?php if ( '' !== $column['content'] ) : ?>
				<div class="builder-text-content">
					<?php Make()->section()->get_content( $column['content'] ); ?>
				</div>
				<?php endif; ?>
			</div>
			<?php if ( $i % $columns_layout_size == 0 ): ?>
				</div>
				<?php if ( $i < sizeof( $text_columns ) ): ?>
					<div class="builder-text-row">
				<?php endif; ?>
			<?php endif; ?>
		<?php $i++; endforeach; endif; ?>
	</div>
	<?php if ( 0 !== $darken ) : ?>
	<div class="builder-section-overlay"></div>
	<?php endif; ?>
</section>
