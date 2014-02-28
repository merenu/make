<?php
/**
 * @package ttf-start
 */
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<header class="entry-header">
		<?php get_template_part( 'partials/entry', 'title' ); ?>
	</header>

	<div class="entry-content">
		<?php the_content(); ?>
		<?php get_template_part( 'partials/entry', 'pagination' ); ?>
	</div>
</article>
