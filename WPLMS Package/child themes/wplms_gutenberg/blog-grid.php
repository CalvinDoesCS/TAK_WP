<?php
/**
 * Template Name: Grid Blog
 */

  get_header(); ?>

  <?php  get_template_part( 'template-parts/content', 'header' ); ?>

	<div class="container mx-auto flex flex-wrap gap-6">
		<div class="entry-content-wrapper p-2 md:p-0"> 
			<div class="blog_grid_view">
			<?php
                 
                 $paged = (get_query_var('paged')) ? get_query_var('paged') : 1; 
                 
                 query_posts(array('post_type'=>'post','paged' => $paged));
                 
                 if ( have_posts() ) : while ( have_posts() ) : the_post();
                    
                    get_template_part( 'template-parts/blog', 'grid' );
                     
                 endwhile;
                 endif;
                 wp_reset_postdata();
                 
             ?>
         	</div>
         	<?php pagination(); ?>
		</div>
	</div>
	
<?php
get_footer();
