<?php 
global $wp_query;
$class='';
if(!empty($wp_query->post_count) && $wp_query->current_post < ($wp_query->post_count-1)){
	 $class= 'mb-4 pb-4 border-b border-slate-200';
}
?>
<article id="post-<?php the_ID(); ?>" <?php post_class($class ); ?>> 

	<header class="sub-entry-header lg:mb-4 p-2 lg:p-0">
		<?php the_title( sprintf( '<h2 class="entry-title text-2xl md:text-3xl font-extrabold leading-tight mb-1 break-all"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h2>' ); ?>
		<div class="post-meta flex flex-wrap flex-grow gap-0.5 font-semibold mt-3 mb-3">
			
			<span class="text-sm text-gray-500"><?php printf('Last updated on %s by %s','<time itemprop="datePublished"  datetime="<?php echo ; ?>" >'.get_the_date().'</time>',get_the_author_posts_link()); ?></span>
		</div>
	</header>

	<?php if ( is_single() ) : ?>
		<div class="entry-content p-2 lg:p-0">
			<?php
			/* translators: %s: Name of current post */
			the_content(
				sprintf(
					__( 'Continue reading %s', 'micronet' ),
					the_title( '<span class="screen-reader-text">"', '"</span>', false )
				)
			);

			
			wp_link_pages(
				array(
					'before'      => '<div class="page-links clear-both flex pt-4"><span class="page-links-title">' . __( 'Pages:', 'micronet' ) . '</span>',
					'after'       => '</div>',
					'link_before' => '<span>',
					'link_after'  => '</span>',
					'pagelink'    => '<span class="screen-reader-text">' . __( 'Page', 'micronet' ) . ' </span>%',
					'separator'   => '<span class="screen-reader-text">, </span>',
				)
			);
			?>
		</div>
		

	<?php else : ?>

		<div class="entry-summary p-2 lg:p-0">
			<?php 
				if(has_post_thumbnail()){
					?>
					<div class="my-4 rounded overflow-hidden">
					<?php the_post_thumbnail(); ?>
					</div>
					<?php
				}
			?>
			<?php 
			global $post;
			if( !empty($post) && strpos( $post->post_content, '<!--more-->' ) ) {
		        the_content(__( 'Read more', 'micronet' )	);
		    }
		    else {
		        the_excerpt();
		    }?>
		</div>

	<?php endif; ?>

</article>
