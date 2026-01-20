<?php
/**
 * Template Name: FullWidth
 */

  get_header(); ?>

<?php if ( have_posts() ) : ?>
<div class="mx-auto flex flex-wrap gap-6">
        <?php
        while ( have_posts() ) :
            the_post();
            ?>

    
        
    
            <div class="entry-content-wrapper p-2 md:p-0"> 
                <?php 
                    the_content(); 
                    do_action('member_profile_content');
                ?>
            </div>
        
    
    
    <?php endwhile; ?>
</div>
    <?php endif; ?>
<?php
get_footer();
