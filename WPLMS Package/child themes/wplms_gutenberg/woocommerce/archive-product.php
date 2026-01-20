<?php

	/**
	 * The Template for displaying product archives, including the main shop page which is a post type archive
	 *
	 * This template can be overridden by copying it to yourtheme/woocommerce/archive-product.php.
	 *
	 * HOWEVER, on occasion WooCommerce will need to update template files and you
	 * (the theme developer) will need to copy the new files to your theme to
	 * maintain compatibility. We try to do this as little as possible, but it does
	 * happen. When this occurs the version of the template file will be bumped and
	 * the readme will list any important changes.
	 *
	 * @see https://docs.woocommerce.com/document/template-structure/
	 * @package WooCommerce/Templates
	 * @version 3.4.0
	 */

	if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


	get_header(vibe_get_header());

	?>
	<section id="title">
		<?php do_action('wplms_before_title'); ?>
	    <div class="container">
	    	<div class="breadcrumbs">
                <?php
					/**
					 * woocommerce_before_main_content hook
					 *
					 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
					 * @hooked woocommerce_breadcrumb - 20
					 */
					do_action('woocommerce_before_main_content');
                ?> 
            </div>
			<h1 class="page-title">
				<?php if ( is_search() ) : ?>
					<?php
						printf( __( 'Search Results: &ldquo;%s&rdquo;', 'woocommerce' ), get_search_query() );
						if ( get_query_var( 'paged' ) )
							printf( __( '&nbsp;&ndash; Page %s', 'woocommerce' ), get_query_var( 'paged' ) );
					?>
				<?php elseif ( is_tax() ) : ?>
					<?php echo single_term_title( "", false ); ?>
				<?php else : ?>
					<?php
						$shop_page_id = '';
		                
	                   $shop_page_id = wc_get_page_id('shop');
		                
						$shop_page = get_post( $shop_page_id );

						echo apply_filters( 'the_title', ( $shop_page_title = get_option( 'woocommerce_shop_page_title' ) ) ? $shop_page_title : $shop_page->post_title );
					?>
				<?php endif; ?>
			</h1>   
	    </div>
	</section>
	<section class="main">
		<div class="container mx-auto my-8 flex items-start flex-wrap md:flex-nowrap gap-6">
			<?php
			if(is_active_sidebar('shopsidebar')){ ?>
        	<div class="sidebar_wrapper">
		        <?php
		                /**
		                * woocommerce_sidebar hook
		                *
		                * @hooked woocommerce_get_sidebar - 10
		                */
		                do_action('woocommerce_sidebar');
		        ?>
		    </div> 
			<?php } ?>
            <div class="post_content_wrapper <?php echo (is_active_sidebar('shopsidebar')?'':'no_active_sidebar'); ?>">
                <div class="shop_products content padder">
					<?php do_action( 'woocommerce_archive_description' ); ?>
			                
				<?php if (woocommerce_product_loop()) : ?>

				<div class="shop_countsorter">			
					<?php do_action('woocommerce_before_shop_loop'); ?>
				</div>	
				<div class="products_wrapper">

					<?php
						
						woocommerce_product_loop_start();
						
						if ( wc_get_loop_prop( 'total' ) ) {
							while ( have_posts() ) {
								the_post();

								/**
								 * Hook: woocommerce_shop_loop.
								 *
								 * @hooked WC_Structured_Data::generate_product_data() - 10
								 */
								do_action( 'woocommerce_shop_loop' );

								wc_get_template_part( 'content', 'product' );
							}
						}
						woocommerce_product_loop_end();

					?>

				</div>

				<?php do_action('woocommerce_after_shop_loop'); ?>

				<?php else : 
					/**
					 * Hook: woocommerce_no_products_found.
					 *
					 * @hooked wc_no_products_found - 10
					 */
					do_action( 'woocommerce_no_products_found' ); 
					endif; ?>

				<div class="clear"></div>

					
				</div>
				<?php
					/**
					 * woocommerce_after_main_content hook
					 *
					 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
					 */
					do_action('woocommerce_after_main_content');
				?>
     		</div>
		 </div>
	</section>
	<?php get_footer( vibe_get_footer() );  



?>            