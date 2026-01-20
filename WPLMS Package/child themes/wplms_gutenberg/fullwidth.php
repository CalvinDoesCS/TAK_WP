<?php
/**
 * Template Name: FullWidth
 */

  get_header(); ?>

<?php if ( have_posts() ) : ?>

		<?php
		while ( have_posts() ) :
			the_post();
			?>
  <?php if(!is_front_page()){get_template_part( 'template-parts/content', 'header' ); } ?>

	<div class="container mx-auto flex flex-wrap gap-6">
		
	
			<div class="entry-content-wrapper p-2 md:p-0"> 
				<?php 
					the_content(); 
					do_action('member_profile_content');
				?>
			</div>
		
	
	</div>
	<?php endwhile; ?>

	<?php endif; ?>
<?php
get_footer();
