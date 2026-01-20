<?php 
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="profile" href="http://gmpg.org/xfn/11">
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="global-site-content-wrapper min-h-screen flex flex-row">
	<header class="site_header app_header h-screen w-64 p-4 flex flex-col gap-4 sticky top-0 justify-between">
		<span class="minimise_side_header">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path fill="none" d="M0 0h24v24H0z"/><path d="M3 4h18v2H3V4zm0 7h12v2H3v-2zm0 7h18v2H3v-2z"/></svg>
		</span>
        <?php
	            $args = apply_filters('wplms-main-menu',array(
	                 'theme_location'  => 'main-menu',
	                 'container'       => 'nav',
	                 'menu_class'      => 'menu flex flex-col',
	                 'items_wrap' => '<ul id="%1$s" class="%2$s">%3$s<li></ul>',
	                 'walker'          => new vibe_walker,
	                 'fallback_cb'     => 'vibe_set_menu'
	             ));
	            wp_nav_menu( $args ); 
        ?>
        <div class="flex flex-col gap-4">
	        <?php
		    if(!empty(vibe_get_option('dark_light_switcher'))){
	            $default_dark_theme = vibe_get_option('default_dark_theme');
	            echo '<span class="dark_light_switch">

	            <span class="light_theme '.(empty($default_dark_theme)?'':'active').'"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path fill="none" d="M0 0h24v24H0z"/><path d="M12 18a6 6 0 1 1 0-12 6 6 0 0 1 0 12zm0-2a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM11 1h2v3h-2V1zm0 19h2v3h-2v-3zM3.515 4.929l1.414-1.414L7.05 5.636 5.636 7.05 3.515 4.93zM16.95 18.364l1.414-1.414 2.121 2.121-1.414 1.414-2.121-2.121zm2.121-14.85l1.414 1.415-2.121 2.121-1.414-1.414 2.121-2.121zM5.636 16.95l1.414 1.414-2.121 2.121-1.414-1.414 2.121-2.121zM23 11v2h-3v-2h3zM4 11v2H1v-2h3z"/></svg><span>'.__('Dark Theme','wplms-app').'</span></span>
	            <span class="dark_theme '.(empty($default_dark_theme)?'active':'').'"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path fill="none" d="M0 0h24v24H0z"/><path d="M10 7a7 7 0 0 0 12 4.9v.1c0 5.523-4.477 10-10 10S2 17.523 2 12 6.477 2 12 2h.1A6.979 6.979 0 0 0 10 7zm-6 5a8 8 0 0 0 15.062 3.762A9 9 0 0 1 8.238 4.938 7.999 7.999 0 0 0 4 12z"/></svg><span>'.__('Light Theme','wplms-app').'</span></span>
	            </span>';
	        }

	        ?>
			<span class="vibebp-login">
				<span class="flex gap-2">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path fill="none" d="M0 0h24v24H0z"/><path d="M4 22a8 8 0 1 1 16 0h-2a6 6 0 1 0-12 0H4zm8-9c-3.315 0-6-2.685-6-6s2.685-6 6-6 6 2.685 6 6-2.685 6-6 6zm0-2c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4z"/></svg>
				<strong class="vibebp-login-text"><?php _e('Sign In','wplms-app'); ?></strong>
				</span>
	        </span>
	    </div>
	</header>
	<div class="site-content-wrapper flex-grow flex-col flex relative z-auto" >
		<header class="main_block_header flex p-3 items-center justify-between sticky top-0 z-40 gap-4">
			<span class="minimise_side_header">
				<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-align-left"><line x1="17" y1="10" x2="3" y2="10"></line><line x1="21" y1="6" x2="3" y2="6"></line><line x1="21" y1="14" x2="3" y2="14"></line><line x1="17" y1="18" x2="3" y2="18"></line></svg>
			</span>
			<span class="flex gap-4 flex-1">
				<?php
	            if(is_front_page()){
		                echo '<h1 id="logo">';
		            }else{
		                echo '<h2 id="logo">';
		            }

		            $url = apply_filters('wplms_logo_url',VIBE_URL.'/assets/images/logo.png','header');
		            if(!empty($url)){
		        ?>
		            <a href="<?php echo vibe_site_url(); ?>"><img src="<?php  echo vibe_sanitizer($url,'url'); ?>" width="100" height="48" alt="<?php echo get_bloginfo('name'); ?>" class="flex justify-center" /></a>
		        <?php
		            }
		            if(is_front_page()){
		                echo '</h1>';
		            }else{
		                echo '</h2>';
		            }
		        ?>
	        	<?php
				if(vibe_get_option('header_search')){
					?>
					<div class="searchbox flex-1 border has-border-color rounded p-1 max-w-2xl">
						<form action="<?php echo home_url(); ?>" class="flex items-center">
							<input type="text" name="s" class="flex-1" placeholder="<?php _e('Hit enter to search...','wplms-app'); ?>" />
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path d="M18.031 16.617l4.283 4.282-1.415 1.415-4.282-4.283A8.96 8.96 0 0 1 11 20c-4.968 0-9-4.032-9-9s4.032-9 9-9 9 4.032 9 9a8.96 8.96 0 0 1-1.969 5.617zm-2.006-.742A6.977 6.977 0 0 0 18 11c0-3.868-3.133-7-7-7-3.868 0-7 3.132-7 7 0 3.867 3.132 7 7 7a6.977 6.977 0 0 0 4.875-1.975l.15-.15z"/></svg>
						</form>
					</div>
				<?php 
					}
				?>
			</span>
			<?php
	            $args = apply_filters('wplms-top-menu',array(
	                'theme_location'  => 'top-menu',
	                'container'       => '',
	                'depth'           => 1,
	                'menu_class'      => 'topmenu flex items-center gap-4 list-none',
	                'fallback_cb'     => 'vibe_set_menu',
	                'items_wrap' => '<ul id="%1$s" class="%2$s">%3$s<li></ul>',
	            ));

		        wp_nav_menu( $args );
	       
				if(vibe_get_option('header_extras') && function_exists('vibebp_plugin_update')){
					?>
					<div class="header_extras justify-end flex items-center gap-4 mx-2">
						<?php do_action('wplms_app_header_extras'); ?>
						<?php if(function_exists('WC')){?>  
							<span class="vibebp-cart"></span><?php 
						} ?>
					</div>
					<?php
				}
				?>
		</header>
		<div class="site-content flex-grow p-0 sm:p-6 z-10 overflow-hidden">
			
			<!-- End introduction -->
		
		
