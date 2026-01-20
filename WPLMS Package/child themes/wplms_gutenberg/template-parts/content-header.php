
<header class="entry-header mb-2 md:mb-6 py-6 md:py-12">
	<div class="container mx-auto flex flex-col p-2 lg:p-0 content_head">
		<?php vibe_breadcrumbs(); ?>
		<?php the_title( sprintf( '<h1 class="entry-title text-2xl lg:text-5xl font-extrabold leading-tight mb-1 break-all"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h1>' ); ?>
		
			<?php
			if(is_singular('service')){
				?>
				<div class="post-meta flex flex-wrap flex-grow gap-0.5 font-semibold mt-3 mb-3"><p><?php the_content(); ?></p></div>
				<?php

			}else if(!is_page() && !is_singular('product')){
				?>
				<div class="post-meta flex flex-wrap flex-grow gap-0.5 font-semibold mt-3 mb-3"><span class="text-sm"><?php printf('Last updated on %s by %s','<a href="'.get_permalink().'"><time itemprop="datePublished">'.get_the_date().'</time></a>',get_the_author_posts_link()); ?></span></div>
				<?php
			}
			?>
		<?php do_action('micronet_after_header'); ?>
	</div>
</header>