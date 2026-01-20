</div>
<footer class="site_footer">
    <div class="<?php echo vibe_get_container(); ?>">
        <div class="footertop">
            <div class="row">
                <?php 
                    if ( !function_exists('dynamic_sidebar')|| !dynamic_sidebar('topfootersidebar') ) : ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="footerbottom">
            <div class="row">
                <?php 
                    if ( !function_exists('dynamic_sidebar')|| !dynamic_sidebar('bottomfootersidebar') ) : ?>
                <?php endif; ?>
            </div>
        </div>
    </div> 
    <div id="scrolltop">
        <a><i class="vicon vicon-angle-double-up"></i><span><?php _e('top','vibe'); ?></span></a>
    </div>
</footer>
</div>
<?php wp_footer(); ?>
</div>
</body>
</html>