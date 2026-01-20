<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<div class="entry-content p-2 lg:p-0">
		<?php
			if(has_post_thumbnail()){
				the_post_thumbnail();
			}
		?>
		<?php
			
			the_content(
				__( 'Continue reading', 'micronet' )				
			);
			

			do_action('micronet_post_content');
			
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
</article>
