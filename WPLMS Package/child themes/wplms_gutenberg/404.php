<?php get_header(); ?>
	<?php 
		if(Empty(vibe_get_option('error404'))):
			?>
			<header class="entry-header  mb-2 md:mb-6 py-6 md:py-12">
				<div class="container mx-auto flex flex-col">
					<?php vibe_breadcrumbs(); ?>
					
					</span><h1 class="entry-title text-2xl lg:text-5xl font-extrabold leading-tight break-all"><?php _ex('Page not found','404 title','micronet'); ?></h1>
					<?php do_action('micronet_after_header'); ?>
				</div>
			</header>
			<div class="container mx-auto my-8 flex items-start flex-wrap gap-6">
				<div class="post_content_wrapper no_active_sidebar">
					
					<p class="mb-4 text-2xl"><?php _e( 'Sorry, the page you are looking for could not be found.', 'micronet' ); ?></p>

					<a href="<?php echo esc_url( home_url() ); ?>" class="button">
							<?php _e( 'Go Home', 'micronet' ); ?></a>
				
				</div>
			</div>
			<?php
			
		else:
			query_posts(['p'=>vibe_get_option('error404')]);
			if ( have_posts() ):
				while ( have_posts() ):
			

				the_post();

			?>
	<?php get_template_part( 'template-parts/content', 'header' ); ?>

	<div class="container mx-auto my-6 flex items-start flex-wrap gap-6">
		<div class="post_content_wrapper no_active_sidebar">
			<?php the_content(); ?>
	</div>
	</div>
	<?php endwhile; ?>

	<?php endif; ?>
	<?php endif; ?>
<?php
get_footer();


