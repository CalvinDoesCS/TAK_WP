<header class="entry-header mb-2 md:mb-6 py-6 md:py-12">
	<div class="container mx-auto flex flex-col p-2 lg:p-0">
		<?php vibe_breadcrumbs(); ?>
		<h1 class="entry-title text-3xl lg:text-5xl font-extrabold leading-tight mb-1">
			<?php echo get_bloginfo('name'); ?></h1>
		<div class="post-meta flex flex-wrap flex-grow gap-0.5 font-semibold mt-3 mb-3">
			<span class="text-sm"><?php echo get_bloginfo('description'); ?></span>
		</div>
		<?php do_action('micronet_after_header'); ?>
	</div>
</header>