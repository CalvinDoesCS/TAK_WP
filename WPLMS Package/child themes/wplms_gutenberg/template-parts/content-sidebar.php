<aside class="sidebar flex flex-wrap gap-6 flex  p-2 md:p-0">
    <?php
    $sidebar = apply_filters('micronet_sidebar','mainsidebar');
    if ( !function_exists('dynamic_sidebar')|| !dynamic_sidebar($sidebar) ) : ?>
    <?php endif; ?>
</aside>
