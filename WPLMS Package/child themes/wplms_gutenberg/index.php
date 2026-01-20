<?php get_header(); ?>


<?php
		if(is_search() || is_archive()){
			get_template_part( 'template-parts/title', get_post_format() );
		}else{
			get_template_part( 'template-parts/archive', 'header' ); 	
		}
	?>
	<div class="container mx-auto my-8 flex items-start flex-wrap gap-6">
		<div class="post_content_wrapper <?php echo (is_active_sidebar( apply_filters('micronet_sidebar','mainsidebar'))?'':'no_active_sidebar'); ?>">
			<?php if ( have_posts() ) : ?>
			<?php
				while ( have_posts() ) :
				the_post();
			?>
			<?php get_template_part( 'template-parts/content', get_post_format() ); ?>	

			<?php endwhile; ?>
			<?php else: ?>
			<span class="text-lg"><?php _ex('No posts found','no search result','micronet'); ?></span>
			<?php endif; ?>
			<?php pagination(); ?>
	</div>
	<?php if(is_active_sidebar('mainsidebar')){?>
	<div class="sidebar_wrapper">
		<?php get_template_part( 'template-parts/content', 'sidebar' ); ?>
	</div>
	<?php } ?>
</div>

<?php
get_footer();
