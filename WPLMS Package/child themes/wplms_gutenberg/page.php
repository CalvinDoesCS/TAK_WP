<?php get_header();  ?>
	<?php if ( have_posts() ) : ?>
	<?php
		while ( have_posts() ) :
			the_post();
			?>
	<?php get_template_part( 'template-parts/content', 'header' ); ?>
	<main class="<?php echo main_class(); ?> ">
	<div class="container mx-auto my-8 flex items-start flex-wrap gap-6">
		<div class="post_content_wrapper <?php echo (is_active_sidebar( apply_filters('micronet_sidebar','mainsidebar'))?'':'no_active_sidebar'); ?>">
	

			<?php echo get_template_part( 'template-parts/content', 'single' ); ?>

			<?php
			// If comments are open or we have at least one comment, load up the comment template.
			if ( comments_open() || get_comments_number() ) :
				comments_template();
			endif;
			?>

		
	</div>
	<?php 
		if( is_active_sidebar( apply_filters('micronet_sidebar','mainsidebar')) ){
			?>
	<div class="sidebar_wrapper">
		<?php get_template_part( 'template-parts/content', 'sidebar' ); ?>
	</div>
	<?php } ?>
	</div>
	</main>
	<?php endwhile; ?>

	<?php endif; ?>
<?php
get_footer();


