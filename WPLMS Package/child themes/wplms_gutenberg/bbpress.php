<?php
if ( ! defined( 'ABSPATH' ) ) exit;
get_header(vibe_get_header());

if ( have_posts() ) : while ( have_posts() ) : the_post();

?>

<header class="entry-header mb-2 md:mb-6 py-6 md:py-12">
    <div class="container mx-auto flex flex-col gap-2 p-2 lg:p-0">
         <?php bbp_breadcrumb(); ?>
        <h1 class="entry-title text-2xl md:text-5xl font-extrabold leading-tight mb-1 break-all">
        <?php the_title(); ?></h1>
        <?php 
        if(bbp_is_forum_archive()){
            _e('All Forums directory','vibe');
        }
        if(bbp_is_single_forum()){
            bbp_forum_subscription_link();
            bbp_single_forum_description();
        }

        if(bbp_is_single_topic()){
            bbp_topic_tag_list(); 
            bbp_single_topic_description();
        }

        ?>

    </div>
</header>

<main class="<?php echo main_class(); ?> ">
    <div class="container mx-auto my-8 flex items-start flex-wrap md:flex-nowrap gap-6">
        <div class="post_content_wrapper">
            <div class="content">
                <?php
                    the_content();
                 ?>
            </div>
        </div>
        <div class="sidebar_wrapper">
            <?php if ( bbp_allow_search() ) : ?>

                <?php bbp_get_template_part( 'form', 'search' ); ?>

            <?php endif; ?>
            <?php
                $sidebar = apply_filters('wplms_sidebar','bbpress');
                if ( !function_exists('dynamic_sidebar')|| !dynamic_sidebar($sidebar) ) : ?>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php
endwhile;
endif;
?>
<?php
get_footer( vibe_get_footer() ); 