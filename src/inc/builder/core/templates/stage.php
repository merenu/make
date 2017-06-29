<?php
/**
 * @package Make
 */

global $ttfmake_sections;
?>

<div class="ttfmake-stage ttfmake-stage-closed" id="ttfmake-stage">
	<?php
	/**
	 * Execute code before the builder stage is displayed.
	 *
	 * @since 1.2.3.
	 */
	do_action( 'make_before_builder_stage' );

	/**
	 * Execute code after the builder stage is displayed.
	 *
	 * @since 1.2.3.
	 */
	do_action( 'make_after_builder_stage' );
?>
	<textarea name="ttfmake-sections-json" id="ttfmake-sections-json" style="display: none;"></textarea>
</div>