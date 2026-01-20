<?php do_action( 'wplms_gutenberg_content_end' ); ?>

</div>

<?php do_action( 'wplms_gutenberg_content_after' ); ?>

<footer  class="site-footer py-12 mt-6" role="contentinfo">
    <div class="container mx-auto footer_sidebar">
        <div class="row">
        <?php 
                    if ( !function_exists('dynamic_sidebar')|| !dynamic_sidebar('topfootersidebar') ) : ?>
                <?php endif; ?>
        </div>
    </div>
    <div id="footerbottom" class="container mx-auto text-center flex flex-wrap gap-4 items-center justify-between">
        <div class="flex items-center">
            <h2 id="footerlogo">
            <?php
                $url = apply_filters('wplms_logo_url',VIBE_URL.'/assets/images/logo.png','footer');
                if(!empty($url)){
            ?>    

                <a href="<?php echo vibe_site_url('','logo'); ?>"><img src="<?php  echo vibe_sanitizer($url,'url'); ?>" alt="<?php echo get_bloginfo('name'); ?>" /></a>
            <?php 
                }
            ?>
            </h2>
            <span class="flex-1"><?php $copyright=vibe_get_option('copyright'); echo (isset($copyright)?do_shortcode($copyright):'&copy; 2023, All rights reserved.'); ?></span>
        </div>
        <div class="flex justify-end">
            <?php
                $footerbottom_right = vibe_get_option('footerbottom_right');
                if(isset($footerbottom_right) && $footerbottom_right){
                    echo '<div id="footer_social_icons">';
                    echo vibe_socialicons();
                    echo '</div>';
                }else{
                    ?>
                    <div id="footermenu">
                        <?php
                                $args = array(
                                    'theme_location'  => 'footer-menu',
                                    'container'       => '',
                                    'depth'           => 1,
                                    'menu_class'      => 'footermenu',
                                    'fallback_cb'     => 'vibe_set_menu',
                                );
                                wp_nav_menu( $args );
                        ?>
                    </div> 
                    <?php
                }
            ?>
        </div>
    </div>
</footer>

</div>
</div>
<?php wp_footer();  ?>
</body>
</html>