<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width">
	<link rel="profile" href="http://gmpg.org/xfn/11">
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'bg-white text-gray-900 antialiased' ); ?>>
<?php wp_body_open(); ?>
<?php do_action( 'wplms_site_before' ); ?>
<div class="hidden site_mobile_menu">
	<div class="flex items-center">
	   	<div class="md:hidden">
			<a href="#" class="p-2" aria-label="<?php _ex('Toggle navigation','aria label','wplms'); ?>" id="close_menu_toggle">
				<svg class="inline-block w-8 h-8" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
				<path d="M6.22566 4.81096C5.83514 4.42044 5.20197 4.42044 4.81145 4.81096C4.42092 5.20148 4.42092 5.83465 4.81145 6.22517L10.5862 11.9999L4.81151 17.7746C4.42098 18.1651 4.42098 18.7983 4.81151 19.1888C5.20203 19.5793 5.8352 19.5793 6.22572 19.1888L12.0004 13.4141L17.7751 19.1888C18.1656 19.5793 18.7988 19.5793 19.1893 19.1888C19.5798 18.7983 19.5798 18.1651 19.1893 17.7746L13.4146 11.9999L19.1893 6.22517C19.5799 5.83465 19.5799 5.20148 19.1893 4.81096C18.7988 4.42044 18.1657 4.42044 17.7751 4.81096L12.0004 10.5857L6.22566 4.81096Z" fill="currentColor"/>
				</svg>
			</a>
		</div>
		<div class="mx-2 flex-1">
			<?php if ( has_custom_logo() ) { ?>
	            <?php the_custom_logo(); ?>
			<?php } else { ?>
				<div class="text-lg uppercase">
					<a href="<?php echo esc_url( home_url() ); ?>" class="font-extrabold text-lg uppercase">
						<img src="<?php echo vibe_get_option('logo')?vibe_get_option('logo'):esc_url(VIBE_URL.'/assets/images/logo_black.png'); ?>" alt="<?php echo get_bloginfo( 'name' ); ?>" width="120" />
					</a>
				</div>
			<?php } ?>
		</div>
	</div>
	<div class="mobile_navigation p-4">
	<?php
		wp_nav_menu(
			array(
				'container_id'    => 'mobile-menu',
				'container_class' => '',
				'menu_class'      => '',
				'theme_location'  => 'mobile',
				'li_class'        => 'md:mx-4',
				'walker'          => new vibe_walker,
				'fallback_cb'     => function(){ echo '<ul class="flex flex-col gap-2">';wp_list_pages(array('depth' => 1, 'number'=>5,'exclude' => '','title_li' => '','sort_column' => 'post_title', 'sort_order' => 'ASC',
	          	)); echo '</ul>';},
			)
		);
	?>
	</div>
</div>
<div class="min-h-screen flex flex-col">
	<?php do_action( 'wplms_header' ); 
	
 ?>

	<header <?php wplms_site_header_attributes(); ?>>
        
		<div class="mx-auto <?php if(!vibe_get_option('wide_header')){echo 'container';}?>">
			<div class="flex justify-between items-center">
				
				<div class="flex justify-between items-center flex-1 gap-4">
				   	
				   	<div class="md:hidden">
						<a href="#" class="p-2" aria-label="<?php _ex('Toggle navigation','aria label','wplms'); ?>" id="open_menu_toggle">
							<svg viewBox="0 0 20 20" class="inline-block w-6 h-6" version="1.1"
								 xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
								<g stroke="none" stroke-width="1" fill="currentColor" fill-rule="evenodd">
									<g class="icon-shape">
										<path d="M0,3 L20,3 L20,5 L0,5 L0,3 Z M0,9 L20,9 L20,11 L0,11 L0,9 Z M0,15 L20,15 L20,17 L0,17 L0,15 Z"
											  ></path>
									</g>
								</g>
							</svg>
						</a>
					</div>
					<div class="mx-2 md:mx-0 flex-1 md:flex-none">
						<?php if ( has_custom_logo() ) { ?>
                            <?php the_custom_logo(); ?>
						<?php } else { ?>
							<div class="text-lg uppercase">
								<a href="<?php echo esc_url( home_url() ); ?>" class="font-extrabold text-lg py-2 uppercase">
									<img src="<?php echo vibe_get_option('logo')?vibe_get_option('logo'):esc_url(VIBE_URL.'/assets/images/logo_black.png'); ?>" alt="<?php echo get_bloginfo( 'name' ); ?>" width="120" />
								</a>
							</div>
						<?php } ?>
					</div>
					<nav class="top-full z-10 px-6 hidden md:flex md:flex-1">
						
						<?php
						
							ob_start();

                            $args = apply_filters('wplms-main-menu',array(
                                 'theme_location'  => 'main-menu',
                                 'container_id'    => 'main-menu',
                                 'container_class' => 'hidden md:mt-4 md:mt-0 md:p-0 md:bg-transparent md:block',
                                 'container'       => 'nav',
                                 'li_class'        => 'md:mx-4',
                                 'menu_class'      => 'md:flex',
                                 'walker'          => new vibe_walker,
                                 'fallback_cb'     => 'vibe_set_menu'
                             ));
                            wp_nav_menu( $args ); 
                        
							
							$nav= ob_get_clean();
							if(!empty($nav)){
								echo '<div class="mega_menu_root_active_highlight hidden md:visible"></div>';
								echo wp_kses_post($nav);
							}

						?>
					</nav>
						
				</div>		
				<div class="flex align-center gap-4">		
					
					<?php
					if(vibe_get_option('header_search')){
						?>
						<div class="wplms_search m-2 flex flex-grow">
							<div class="wplms_live_search border p-2 r-1 flex flex-grow items-center rounded">
								<form class="flex items-center w-full" method="GET" action="<?php echo home_url(); ?>">
									<input type="text" name="s" class="w-full peer focus:outline-none invisible sm-visible " placeholder="<?php _e('Press enter to search ... ','wplms')?>" <?php echo empty($_GET['s'])?'':'value="'.$_GET['s'].'"';?>/>
									<span class="vicon vicon-search hover:visible"></span>
								</form>
							</div>
						</div>
						<?php
					}
					
						?>
					<div class="header_extras justify-end flex items-center gap-4 mx-2">
						<?php do_action('wplms_header_extras'); ?>
						<?php if(function_exists('WC')){?>  
							<span class="vibebp-cart"></span><?php 
						} ?>
						<a class="vibebp-login button" href="<?php echo (function_exists('vibebp_get_setting') && vibebp_get_setting('bp_single_page') && empty(vibebp_get_setting('global_login')))?get_permalink(vibebp_get_setting('bp_single_page')):'#'?>"><?php _ex('Sign In','login header','wplms'); ?></a>
					</div>
					
				</div>
			</div>
	</div>
	</header>
	<div class="site-content-wrapper flex-grow flex-col flex relative z-auto" >
	<div id="content" class="site-content flex-grow">
		<!-- End introduction -->

		<?php do_action( 'wplms_content_start' ); ?>
		
		
