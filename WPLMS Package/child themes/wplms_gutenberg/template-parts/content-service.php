
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<div class="entry-content p-2 lg:p-0">
		<?php 

			$settings = get_option(VIBE_APPOINTMENTS_OPTION);
			if(!empty($settings['appointments_directory_page'])){

	           	$layout = new WP_Query(apply_filters('vbp_single_service_layout',array(
	                'post_type'=>'page',
	                'posts_per_page'=>1,
	                'post__in'=>[$settings['appointments_directory_page']],
	            )));

	            if($layout->have_posts()){
	                while($layout->have_posts()){
	                    $layout->the_post();
	                    global $post;
	                    setup_postdata($post);
	                    $content=$post->post_content;
	                    
	                    the_content();
	                    if(class_exists('\Elementor\Frontend')){
	                        $elementorFrontend = new \Elementor\Frontend();
	                        $elementorFrontend->enqueue_scripts();
	                        $elementorFrontend->enqueue_styles();
	                    }
	                    
	                }
	            }
            }
        ?>
	</div>
</article>



					                