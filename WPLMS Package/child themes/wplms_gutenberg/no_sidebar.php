<?php
/**
 * Template Name: No Sidebar
 */

  get_header(); 
  ?>

	<div class="mx-auto flex flex-wrap gap-6">
		
	<?php if ( have_posts() ) : ?>

		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<div class="entry-content-wrapper  p-2 md:p-0">
				<?php the_content(); ?>
			</div>
		<?php endwhile; ?>

	<?php endif; ?>
	
	</div>

<?php
get_footer();
