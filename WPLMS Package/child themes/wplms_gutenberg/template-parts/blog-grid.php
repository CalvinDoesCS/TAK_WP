<?php 
if ( ! defined( 'ABSPATH' ) ) exit;

?>

<div class="flex flex-col gap-4 p-4 rounded border border-slate-100 <?php echo get_post_format(); ?>">
    <?php if(has_post_thumbnail(get_the_ID())){ ?>
         <div class="featured rounded overflow-hidden">
            <a href="<?php echo get_permalink() ?>"><?php echo get_the_post_thumbnail(get_the_ID(),'full'); ?></a>
        </div>
    <?php } ?>
    <div class="flex gap-4 justify-between">
       
        <div class="flex flex-col gap-2">
            <span class="primary-color"><?php echo get_the_category_list('',''); ?></span>
            <h3 class="text-2xl"><a href="<?php echo get_permalink(); ?>"><?php echo get_the_title(); ?></a></h3>
            <span class="blogpost_style2_date"><?php echo get_the_time('M j,y'); ?></span>
        </div>
         <?php
            $name = get_the_author_meta( 'display_name' );
            echo '<a href="'.get_author_posts_url( get_the_author_meta( 'ID' ) ).'" 
        title="'.$name.'" class="blogpost_author h-8 w-8 basis-8 shrink-0 rounded-full overflow-hidden">'.((function_exists('bp_core_fetch_avatar'))?bp_core_fetch_avatar(array(
                'item_id' => get_the_author_meta( 'ID' ),
                'object'  => 'user'
            )):$name).'</a>';
        ?>
    </div>

    <div class="excerpt flex flex-col gap-4">
        <p><?php echo get_the_excerpt(); ?></p>
        <a href="<?php echo get_permalink(); ?>" class="link primary-color"><?php echo __('Read More','micronet'); ?></a>
    </div>
</div>
