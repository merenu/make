<?php
/**
 * @package Make
 */

global $ttfmake_sections;

/**
 * Execute code before the builder stage is displayed.
 *
 * @since 1.2.3.
 */
do_action( 'make_before_builder_stage' );
?>

<div class="ttfmake-stage ttfmake-stage-closed" id="ttfmake-stage"></div>

<textarea name="ttfmake-sections-json" id="ttfmake-sections-json" style="display: none;"></textarea>

<?php
/**
 * Execute code after the builder stage is displayed.
 *
 * @since 1.2.3.
 */
do_action( 'make_after_builder_stage' );
?>