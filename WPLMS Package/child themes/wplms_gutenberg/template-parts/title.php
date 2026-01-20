<?php

?>
<header class="entry-header mb-2 md:mb-6 py-6 md:py-12">
	<div class="container mx-auto flex flex-col p-2 lg:p-0">
	<?php
global $wp_query;
	if(is_search()){
	?>
	<h1 class="entry-title text-2xl md:text-5xl font-extrabold leading-tight mb-1 break-all">
		<?php 
		echo sprintf(__('Search Results for "%s"', 'micronet'), get_search_query());?>
			
		</h1>
		<p><?php
		echo (esc_attr($wp_query->found_posts) == 1)?__('1 result found','micronet'):sprintf(__('%d results found','micronet'),$wp_query->found_posts);  ?></p>
		<form>
			<div class="flex items-center justify-center max-w-3xl mt-4">
			    <div class="flex border-2 w-full rounded">
			        <input type="text" name="s" value="<?php the_search_query(); ?>" class=" grow shrink px-4 py-2 bg-white text-gray-800 focus:outline-none" placeholder="<?php _e('Search...','micronet')?>">
			        <span class="flex items-center basis-8 justify-center  bg-white">
			            <svg class="w-6 h-6 text-gray-600" fill="fill-gray-800" xmlns="http://www.w3.org/2000/svg"
			                viewBox="0 0 24 24">
			                <path
			                    d="M16.32 14.9l5.39 5.4a1 1 0 0 1-1.42 1.4l-5.38-5.38a8 8 0 1 1 1.41-1.41zM10 16a6 6 0 1 0 0-12 6 6 0 0 0 0 12z" />
			            </svg>
			        </span>
			    </div>
			</div>
		</form>
		<?php
	}else if(is_author()){
		?><h1 class="entry-title text-2xl md:text-5xl font-extrabold leading-tight mb-1 break-all">
		<?php 
			echo sprintf(__('Posts by %s','micronet'),apply_filters('author_archive_title',get_the_author_meta('display_name')));
        ?>
	    </h1>
	    <p><?php echo 
		 sprintf(__('%d results found','micronet'),$wp_query->found_posts); ?></p>
		 <?php
	}else if(function_exists('is_shop') && is_shop() && apply_filters( 'woocommerce_show_page_title', true )){ 
		
			/**
			 * Hook: woocommerce_before_main_content.
			 *
			 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
			 * @hooked woocommerce_breadcrumb - 20
			 * @hooked WC_Structured_Data::generate_website_data() - 30
			 */
			do_action( 'woocommerce_before_main_content' );
		?>
		<h1 class="entry-title text-2xl md:text-5xl font-extrabold leading-tight mb-1 woocommerce-products-header__title page-title"><?php woocommerce_page_title(); ?></h1>
		<?php
			/**
			 * Hook: woocommerce_archive_description.
			 *
			 * @hooked woocommerce_taxonomy_archive_description - 10
			 * @hooked woocommerce_product_archive_description - 10
			 */
			do_action( 'woocommerce_archive_description' );

		}else if(is_archive()){

		?><h1 class="entry-title text-2xl md:text-5xl font-extrabold leading-tight mb-1">
		<?php 
		if(is_month()){
	                single_month_title(' ');
	            }elseif(is_year()){
	                echo get_the_time('Y');
	            }else if(is_category()){
	                echo single_cat_title();
	            }else if(is_tag()){
	                 single_tag_title();
	            }else if(is_tax()){
	                single_term_title();
	            }else{
	                post_type_archive_title();
	            }
	        ?>
	    </h1>
	    <p><?php echo empty(term_description())?
		 sprintf(__('%d results found','micronet'),$wp_query->found_posts):term_description(); ?></p>
<?php
	}
?>
</div>
</header>
