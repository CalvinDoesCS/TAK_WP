<?php
/**
 * The Template for displaying all single products.
 *
 * Override this template by copying it to yourtheme/woocommerce/single-product.php
 *
 * @author      WooThemes
 * @package     WooCommerce/Templates
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

get_header( vibe_get_header() ); ?>
<?php
$header = vibe_get_customizer('header_style');
if($header == 'transparent' || $header == 'generic'){
    echo '<section id="title">';
    do_action('wplms_before_title'); 
    echo '</section>';
}
?>
<main class="<?php echo main_class(); ?> ">
    <div class="container mx-auto my-8 flex items-start flex-wrap md:flex-nowrap gap-6">
        <div class="post_content_wrapper">

            <?php
                /**
                 * woocommerce_before_main_content hook
                 *
                 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
                 * @hooked woocommerce_breadcrumb - 20
                 */
                do_action( 'woocommerce_before_main_content' );
            ?>

                <?php while ( have_posts() ) : the_post(); ?>

                    <?php wc_get_template_part( 'content', 'single-product' ); ?>

                <?php endwhile; // end of the loop. ?>

            <?php
                /**
                 * woocommerce_after_main_content hook
                 *
                 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
                 */
                do_action( 'woocommerce_after_main_content' );
            ?>
        </div>  
        <div class="sidebar_wrapper">
            
            <div class="widget">
                <div class="woocart">
                <?php
                    the_widget('WC_Widget_Cart', 'title=0&hide_if_empty=1');
                ?>
                </div>
            </div>
            <?php
                /**
                 * woocommerce_sidebar hook
                 *
                 * @hooked woocommerce_get_sidebar - 10
                 */
                do_action( 'woocommerce_sidebar' );
            ?>
        </div>
   </div>
</main>

<?php get_footer( vibe_get_footer() );  