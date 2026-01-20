<?php
/**
 * Enqueue functions for wplms-app
 *
 * @author      VibeThemes
 * @category    Admin
 * @package     Initialization
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;


class Wplms_App_Theme{

    public static $instance;
    
    public static function init(){

        if ( is_null( self::$instance ) )
            self::$instance = new Wplms_App_Theme();

        return self::$instance;
    }

    private function __construct(){

    	add_filter('wplms_customizer_config',[$this,'customizer']);
    	add_action('init',[$this,'footer']);
    	add_filter('widget_nav_menu_args',[$this,'widgets']);
    	add_filter('wplms_site_header_style',[$this,'header_style']);
		add_filter('wplms_site_footer_style',[$this,'footer_style']);
		add_action('wp_enqueue_scripts',[$this,'scripts'],11);

		add_action( 'wp_nav_menu_item_custom_fields',array($this, 'menu_icon'), 10, 2 );
		add_action( 'wp_update_nav_menu_item', array($this, 'save_menu_icon'), 10, 3 );

		add_filter('vibebp_featured_post_type_styles_options',[$this,'wplms_vibe_course_blocks']);
		add_filter('vibebp_featured_taxonomy_styles_options',[$this,'wplms_taxonomy_blocks']);
		add_filter('vibebp_featured_members_styles_options',[$this,'wplms_member_blocks']);

		add_action('vibebp_featured_style',[$this,'wplms_vibe_course_blocks_html'],10,2);

		add_filter('vibebp_carousel_args',[$this,'carousel_args'],10,2);

		add_filter('vibe_option_custom_sections',[$this,'header_extras']);
		
		add_shortcode('mega_course_block',[$this,'mega_course_block']);
		add_shortcode('instructor_courses',[$this,'instructor_courses']);
		add_shortcode('my_courses',[$this,'my_courses']);


			add_action( 'after_setup_theme', [$this,'remove_widget_blocks_theme_support'] );
			add_filter('wplms_demo_styles',[$this,'demo_style']);
			add_filter('wplms_default_site_style',[$this,'default_style']);
			add_filter('wplms_required_plugins',[$this,'app_plugins']);
			add_action('wplms_envato_setup_design_save',[$this,'handle_demo_adjustments'],999999);
			add_action('wplms_setup_wizard_page_setup',[$this,'page_setup']);

		add_filter( 'theme_page_templates', [$this,'remove_page_templates']);
    }


    function remove_page_templates( $templates ) {
	    unset( $templates['allinstructors.php'] );
	    unset( $templates['blog.php'] );
	    unset( $templates['blog1.php'] );
	    unset( $templates['blog2.php'] );
	    unset( $templates['blog3.php'] );
	    unset( $templates['left-sidebar-page.php'] );
	    unset( $templates['login-page.php'] );
	    unset( $templates['notes_discussion.php'] );
	    unset( $templates['page-instructors.php'] );
	    unset( $templates['page-members.php'] );
	    unset( $templates['search-incourse.php'] );
	    return $templates;
	}
    function page_setup(){
		if(class_exists('VibeBP_SetupWizard')){
			$theme_style = get_stylesheet_directory_uri().'/js/bp.json';
			$wizrd = VibeBP_SetupWizard::init();
			$wizrd->import_default_xprofile($theme_style);
		}
    }

    function handle_demo_adjustments($demo_style=null){
		
		$locations = get_theme_mod('nav_menu_locations');
		
		$locations['top-menu'] = 125;
		$locations['main-menu'] = 126;
		set_theme_mod( 'nav_menu_locations', $locations );
		
		vibe_update_option('offload_scripts',2);
		vibe_update_option('header_extras',1);

		vibe_update_option('offload_scripts',2);
		vibe_update_option('header_search',1);
		vibe_update_option('header_extras',1);
		vibe_update_option('dark_light_theme',1);
		vibe_update_option('header_extras',1);
		vibe_update_option('offload_fonts',1);

		if(defined('VIBE_BP_SETTINGS')){
			global $wpdb;
			$apppage = $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_name=%s",'app'));
			if(!empty($apppage )){
				$all_settings = get_option(VIBE_BP_SETTINGS);
				if(empty($all_settings)){
					$all_settings = array();
				}
				if(empty($all_settings['general'])){
					$all_settings['general'] = array();
				}
					
				$all_settings['general']['bp_single_page'] = $apppage;
				
				update_option(VIBE_BP_SETTINGS,$all_settings);
			}
		}
	}

    function app_plugins($args){

    	foreach($args as $k=>$v){
    		if(in_array($v['slug'],['layerslider','revslider', 'js_composer','elementor'])){
    			unset($args[$k]);
    		}
    	}
    	return $args;
    }

    function default_style($style){
    	return 'mooc';
    }

    function demo_style($args){
				$args=[
					'mooc'=>
						array(
						'label'=>'MOOC',
						'src' => 'https://demos.wplms.io/learningcenter/wp-content/uploads/2022/12/mooc-1.png',
						'version'=>4,
						'installation_type'=>array('instructor','mooc','academy','university'),
						'link'=>'https://demos.wplms.io/mooc/',
						'plugins'=>array('vibebp','wplms_plugin','buddypress','bbpress','vibe-helpdesk','vibe-calendar','elementor','woocommerce')
					),
					array(
						'label'=>'App',
						'src' => 'http://demos.wplms.io/school/wp-content/uploads/2023/06/coming_soon.png',
						'version'=>4,
						'installation_type'=>array('instructor','mooc','academy','university'),
						'link'=>'https://demos.wplms.io/mooc/',
						'plugins'=>array('vibebp','wplms_plugin','buddypress','bbpress','vibe-helpdesk','vibe-calendar','elementor','woocommerce')
					)
				];
	
			return $args;
		}

    function remove_widget_blocks_theme_support() {
		    remove_theme_support( 'widgets-block-editor' );
		}

    function header_extras($args){
    	

    	$args[1]['fields'][]=[
    		'id' => 'header_extras',
			'type' => 'button_set',
			'title' => esc_html__('Show Login / Cart in Header', 'wplms-app'), 
			'sub_desc' => esc_html__('Login link and cart appears in header.' , 'wplms-app'),
			'desc' => esc_html__('Cart only appears when WooCommerce installed.', 'wplms-app'),
			'options' => array('0' => esc_html__('Disable','wplms-app'),'1' => esc_html__('Enable','wplms-app')),
			'std' => '0'
    	];
    	$args[1]['fields'][]=[
    		'id' => 'header_search',
			'type' => 'button_set',
			'title' => esc_html__('Header Search', 'wplms-app'), 
			'sub_desc' => esc_html__('Show search bar in header.' , 'wplms-app'),
			'desc' => esc_html__('Search Bar shown in header.', 'wplms-app'),
			'options' => array('0' => esc_html__('Disable','wplms-app'),'1' => esc_html__('Enable','wplms-app')),
			'std' => '0'
    	];
    	$args[1]['fields'][]=[
    		'id' => 'dark_light_switcher',
			'type' => 'button_set',
			'title' => esc_html__('Dark Light Switch', 'wplms-app'), 
			'sub_desc' => esc_html__('enable a dark/ light switch' , 'wplms-app'),
			'desc' => esc_html__('Dark to Light Theme switcher.', 'wplms-app'),
			'options' => array('0' => esc_html__('No','wplms-app'),'1' => esc_html__('Yes','wplms-app')),
		'std' => '0'
    	];
    	return $args;
    }
    function carousel_args($args,$atts){

  		$args['breakpoints'] =[
	        '@0.00' => [
	          'slidesPerView'=>1,
	          'spaceBetween'=> 10,
	        ],
	        '@0.75'=>[
	          'slidesPerView'=> 2,
	          'spaceBetween'=> 20,
	        ],
	        '@1.00'=> [
	          'slidesPerView'=> 3,
	          'spaceBetween'=> 40,
	        ],
	        '@1.50'=> [
	          'slidesPerView'=> 4,
	          'spaceBetween'=> 50,
	        ]
      	];
		return $args;
  	}

    function wplms_taxonomy_blocks($styles){
    	$styles['course_tax']='Course Category Block';
    	return $styles;
    }

    function wplms_member_blocks($styles){
    	$styles['course_instructor']='Course Instructor Block';
    	return $styles;
    }

	function wplms_vibe_course_blocks($styles){
	    $course_styles = ['course','course2','course3','course4','course5'
	,'course6','course7','course8','course9','course10','generic','simple'];
	    foreach($course_styles as $style){
	        $styles[$style]=$style;
	    }
	    return $styles;
	}

	function wplms_vibe_course_blocks_html($custom_post,$style){

	    if(in_array($style,['course','course2','course3','course4','course5'
	    ,'course6','course7','course8','course9','course10','generic','simple'])){
	     	echo thumbnail_generator($custom_post,$style);
	    }

	    if($style == 'course_tax'){
	    	$term = $custom_post;
	    	$thumbnail_id = get_term_meta( $term->term_id, 'course_cat_thumbnail_id', true );

	    	if(!empty($thumbnail_id)){
	    		$img = wp_get_attachment_image_src($thumbnail_id,'full');
	    		$img = 'url('.$img[0].')';
	    	}else{
	    		$img = 'linear-gradient(59.94deg,#FECC96,#D93F5C 40%,#5cbbfb 100%)';
	    	}
	    	echo '<div class="wplms_app_tax_block course_cat '.$term->term_slug.'" style="background:'.$img.'"><a href="'.get_category_link($term->term_id ).'"><span class="flex flex-col gap-2"><strong>'.$term->name.'</strong><span class="text-sm">'.$term->description.'</span></span></a></div>';
	    }

	    if($style == 'course_instructor'){
	    	$post = $custom_post;

	    	

	        $avatar = bp_core_fetch_avatar(array(
	            'item_id'   => $post->id,
	            'object'    => 'user',
	            'type'      =>'full',
	            'html'      => false
	        ));
	        $link = bp_core_get_user_domain($post->id);
	        $member_type = bp_get_member_type($post->id);
	        $name = bp_core_get_user_displayname($post->id);
	        $types = bp_get_member_types(array(),'objects');

	        $rating = wplms_get_instructor_average_rating($post->id);
	        $review_count = wplms_get_instructor_rating_count($post->id);
	        $student_count = wplms_get_instructor_student_count($post->id);
            $course_count = bp_course_get_instructor_course_count_for_user($post->id);

           
	    
	        ?>
	        <div class="course_instructor_featured_block_wrapper member_'<?php echo $post->id; ?> flex flex-col" >
	            
	            <div class="member_background">
	                <div class="flex gap-4 flex-col">
	                    <a href="<?php echo $link;?>" class="flex-1 basis-12">
	                        <img src="<?php echo $avatar; ?>" alt="user profile image" class="member_avatar" />
	                    </a>
	                    <div class="flex flex-2">
	                        <a href="<?php echo $link;?>" class="flex-col flex">
	                            <strong><?php echo esc_attr( $name ); ?></strong>
	                            <span class="flex gap-2 items-center"><span><?php echo round($rating,2); ?></span><?php echo bp_course_display_rating($rating); ?> [<?php echo $review_count; ?>] </span>
	                         </a>
	                    </div>
	                </div>
	            </div>
	            <div class="member_info flex flex-col gap-4">
	                
	                <span class="flex-1">	                
	                </span>
	                <div class="instructor_extras">
	                    <span class="flex flex-1 justify-between">
	                        <span class="flex gap-2">
		                        <svg width="16px" height="16px" viewBox="0 0 24 24" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:2;">
		                        <g transform="matrix(1,0,0,1,0.188075,7.38624)">
		                            <g transform="matrix(0.211691,0,0,0.216207,-58.5149,-50.4719)">
		                                <path d="M318.267,265.906C318.267,258.215 324.524,251.958 332.214,251.958C339.905,251.958 346.162,258.215 346.162,265.906C346.162,273.596 339.905,279.853 332.214,279.853C324.524,279.853 318.267,273.596 318.267,265.906ZM342.704,281.755C347.838,278.346 351.233,272.518 351.233,265.906C351.233,255.418 342.702,246.887 332.214,246.887C321.727,246.887 313.195,255.418 313.195,265.906C313.195,272.518 316.59,278.346 321.725,281.755C310.106,286.042 301.784,297.195 301.784,310.284L306.855,310.284C306.855,296.3 318.231,284.925 332.214,284.925C346.198,284.925 357.573,296.3 357.573,310.284L362.645,310.284C362.645,297.195 354.322,286.042 342.704,281.755Z" style="fill-rule:nonzero;"/>
		                            </g>
		                            <g transform="matrix(0.211691,0,0,0.216207,-58.5149,-50.4719)">
		                                <path d="M368.062,269.076C373.197,265.667 376.592,259.838 376.592,253.226C376.592,242.739 368.061,234.208 357.573,234.208C352.492,234.208 347.882,236.221 344.467,239.479C343.202,240.685 342.109,242.063 341.213,243.576C342.795,244.215 344.301,245.005 345.696,245.951C346.593,244.492 347.747,243.213 349.1,242.173C351.452,240.366 354.385,239.279 357.573,239.279C365.264,239.279 371.52,245.536 371.52,253.226C371.52,259.61 367.203,264.993 361.34,266.643C360.14,266.98 358.88,267.174 357.573,267.174C357.124,267.174 356.683,267.148 356.244,267.106C356.153,268.932 355.867,270.704 355.391,272.401C356.115,272.338 356.832,272.246 357.573,272.246C358.603,272.246 359.612,272.325 360.61,272.445C373.164,273.953 382.932,284.651 382.932,297.604L388.004,297.604C388.004,284.516 379.681,273.363 368.062,269.076Z" style="fill-opacity:0.8;fill-rule:nonzero;"/>
		                            </g>
		                            <g transform="matrix(0.211691,0,0,0.216207,-58.5149,-50.4719)">
		                                <path d="M306.855,272.246C307.597,272.246 308.313,272.338 309.038,272.401C308.562,270.704 308.275,268.932 308.185,267.106C307.747,267.148 307.304,267.174 306.855,267.174C305.549,267.174 304.289,266.98 303.089,266.643C297.225,264.993 292.908,259.61 292.908,253.226C292.908,245.536 299.165,239.279 306.855,239.279C310.044,239.279 312.977,240.366 315.329,242.173C316.682,243.213 317.835,244.492 318.732,245.951C320.128,245.005 321.633,244.215 323.215,243.576C322.32,242.063 321.227,240.685 319.962,239.479C316.547,236.221 311.936,234.208 306.855,234.208C296.368,234.208 287.836,242.739 287.836,253.226C287.836,259.838 291.231,265.667 296.366,269.076C284.748,273.363 276.425,284.516 276.425,297.604L281.497,297.604C281.497,284.651 291.264,273.953 303.818,272.445C304.816,272.325 305.826,272.246 306.855,272.246Z" style="fill-opacity:0.8;fill-rule:nonzero;"/>
		                            </g>
		                        </g>
		                    </svg>
	                    	<span><?php echo $student_count; ?></span>
	                    </span>
	                    <a href="<?php echo $link;?>" class="link"><?php echo esc_html__('View Profile','wplms_app'); ?></a>
	                </div>
	            </div>
	        </div>
	        <?php
	    }
	}


	
	function customizer($args){
	    unset($args['theme']['theme_skin']);
	    //unset($args['layouts']);
	    unset($args['header']['header_style']);
	    unset($args['header']['login_style']);
	    return $args;
	}

	
	function footer(){
		$r = WPLMS_Actions::init();	
		remove_action('wp_footer',array($r,'search'));
		remove_action("wp_head","print_customizer_style",99);
	}


	
	function widgets($args){
	    if(empty($args['walker'])){
	        return array_merge( $args, array(
	            'walker' => new Vibe_Menu_Icon_Walker(),
	       ));
	    }
	   return $args;
	}



	function header_style($style){
		return '';
	}
	
	function footer_style($style){
		return '';
	}

	function scripts(){

		wp_dequeue_style('wplms-core');
		wp_dequeue_style('wplms-core');
	    wp_dequeue_style('wplms-v4style');  
	    wp_dequeue_style('wplms-header');  

	    $theme_skin = vibe_get_customizer('theme_skin');
	  	if(!empty($theme_skin)){
	    	wp_dequeue_style($theme_skin);
		}

		wp_enqueue_style('roboto_slab_font',get_stylesheet_directory_uri().'/css/default_font.css');
		wp_enqueue_style('wplms_app',get_stylesheet_directory_uri().'/css/app.css',[],WPLMS_APP_THEME_VERSION);
		wp_enqueue_script('wplms_app',get_stylesheet_directory_uri().'/js/app.js',[],WPLMS_APP_THEME_VERSION);

		/*=== Enqueing Google Web Fonts =====*/
         $font_string='';
         $google_fonts=vibe_get_option('google_fonts');
         
         if(!empty($google_fonts) && is_array($google_fonts)){
            $font_weights = array();
            $font_subsets = array();
            foreach($google_fonts as $font){

              $font_var = explode('-',$font);

              if(!empty($font_weights[$font_var[0]]) && is_array($font_weights[$font_var[0]]) && isset($font_var[1])){
                if(!in_array($font_var[1],$font_weights[$font_var[0]]))
                  $font_weights[$font_var[0]][] = $font_var[1];
              }else{
                if(isset($font_var[1]))
                  $font_weights[$font_var[0]] = array($font_var[1]);
              }
              if(isset($font_var[2]))
              $font_subsets[] = $font_var[2];
            }

            if(!empty($font_weights)){
              foreach($font_weights as $font_name => $font_weight){
                $strings[$font_name] = implode(',',$font_weight);
              }
            }

            if(isset($strings) && is_array($strings)){
              foreach($strings as $key => $str){
                if($key){
                  $key = str_replace(' ','+',$key);
                  $font_string .= $key.':'.$str.'|';
                }
              }
              $font_string = substr($font_string, 0, -1);
            }

            if(isset($font_subsets) && is_array($font_subsets)){
              $font_subsets = array_unique($font_subsets);
              if(!empty($font_subsets)){
                $font_string.='&subsets='.implode(',',$font_subsets);
              }  
            }
            
            if(!empty($font_string)){
              $query_args = apply_filters('vibe_font_query_args',array(
              'family' => $font_string,
              'display'=>'swap'
              ));
              wp_enqueue_style('google-webfonts',
              esc_url(add_query_arg($query_args, "//fonts.googleapis.com/css" )),
              array(), null);
            }

         } // End Google Fonts
		$customizer_css = 'body{';
		
		$vibe_customizer  = wplms_theme_get_customizer_config();

		if(!empty($vibe_customizer['controls'])){
			foreach($vibe_customizer['controls'] as $section){
				if(!empty($section)){
					foreach($section as $k => $v){						
						$v = vibe_get_customizer($k);
						if(!Empty($v)){
							if($k != 'custom_css'){
								$customizer_css .= '--'.$k.':'.(is_numeric($v)?$v.'px':$v).';';		
							}
							
						}
						
					}
				}
			}
		}

		$bg = vibe_get_option('title_bg');
		if(!empty($bg)){
			$customizer_css .= '--header-bg:'.$bg.';';
		}
		$color = vibe_get_option('title_color');
		if(!empty($bg)){
			$customizer_css .= '--header-color:'.$color.';';
		}
		$customizer_css .='}';
		$customizer_css .= vibe_get_customizer('custom_css');

		wp_add_inline_style('wplms_app',$customizer_css);
	}

    function menu_icon( $item_id, $item ) {
		
		$menu_icon = '';
		if(function_Exists('vicon_list'))
		if(empty($item->classes) || !(!empty($item->classes && !empty($item->classes[0]) &&$item->classes[0]=='bp-menu' && !empty($item->classes[1]))) ){
			$menu_icon = get_post_meta($item_id,'menu_icon',true);
			$icons = vicon_list();
			$icons[]='CUSTOM';
			?>
			<div style="clear: both;">
			    <span class="description"><?php esc_html_e( "Menu icon", 'wplms-app' ); ?></span><br />
			    <input type="hidden" class="nav-menu-id" value="<?php echo intval($item_id) ;?>" />
			    <div class="logged-input-holder">
			    	<a class="select_vicon_popup" data-id=<?php echo intval($item_id) ;?>><?php echo esc_html_x('Select Icon','icon selector menu','wplms-app'); ?></a>
			        <input type="hidden" name="menu_icon[<?php echo intval($item_id) ;?>]" id="menu-icon-<?php echo intval($item_id) ;?>" value="<?php echo esc_attr($menu_icon); ?>" />
			    </div>
			</div>
			<?php
		}
	}

	function save_menu_icon( $menu_id, $menu_item_db_id, $args ) {

		if(!current_user_can('manage_options'))
			return;
		

		if ( !empty( $_POST['menu_icon'][$menu_item_db_id]  ) ) {
			
			if(strlen($_POST['menu_icon'][$menu_item_db_id]) > 120){
				$sanitized_data = $this->wp_kses( $_POST['menu_icon'][$menu_item_db_id] );
			}else{
				$sanitized_data = sanitize_text_field($_POST['menu_icon'][$menu_item_db_id]);
			}
			
			update_post_meta($menu_item_db_id,'menu_icon',$sanitized_data);
			
		} else {
			delete_post_meta($menu_item_db_id,'menu_icon');
		}
		
	}


	function wp_kses($data){
		$kses_defaults = wp_kses_allowed_html( 'post' );

		$svg_args = array(
		    'svg'   => array(
		        'class' => true,
		        'aria-hidden' => true,
		        'aria-labelledby' => true,
		        'role' => true,
		        'xmlns' => true,
		        'width' => true,
		        'height' => true,
		        'viewbox' => true, // <= Must be lower case!
		    ),
		    'g'     => array( 'fill' => true ),
		    'title' => array( 'title' => true ),
		    'path'  => array( 'd' => true, 'fill' => true,  ),
		);

		$allowed_tags = array_merge( $kses_defaults, $svg_args );

		return wp_kses( $data, $allowed_tags );
	}

	function mega_course_block($attr,$content=null){

	 	$init = WPLMS_4_Init::init();
	 	if(!empty($attr['id'])){
	 		$id = $attr['id'];
	 	}else if(empty($attr['id']) && !empty($init->course_id)){
          	$id = $init->course_id;
        }
        ob_start();
		?>
		<div class="mega_course_blocks">
			<div class="course_block_tabs flex flex-col gap-4">
				<input type="radio" name="course_tab" class="hidden in_tab_radio" id="in_course_desc"/>
				<input type="radio" name="course_tab" class="hidden in_tab_radio" id="in_course_curriculum"/>
				<input type="radio" name="course_tab" class="hidden in_tab_radio" id="in_course_reviews"/>
				<div class="in_tabs flex flex-wrap in_tab_radio gap-4">
					<label for="in_course_desc" class="in_tab">
						<span class="flex gap-1 items-center"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path fill="currentColor" d="M21 18v2H3v-2h18zM17.404 3.904L22 8.5l-4.596 4.596-1.414-1.414L19.172 8.5 15.99 5.318l1.414-1.414zM12 11v2H3v-2h9zm0-7v2H3V4h9z"/></svg>
						<span><?php _e('Description','wplms-app'); ?></span></span>
					</label>
					<label for="in_course_curriculum" class="in_tab">
						<span class="flex gap-1 items-center">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path fill="currentColor" d="M20 22H4a1 1 0 0 1-1-1V3a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v18a1 1 0 0 1-1 1zm-1-2V4H5v16h14zM8 7h8v2H8V7zm0 4h8v2H8v-2zm0 4h8v2H8v-2z"/></svg>
							<span><?php _e('Curriculum','wplms-app'); ?></span>
						</span>
					</label>
					<label for="in_course_reviews" class="in_tab">
						<span class="flex gap-1 items-center">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24"><path fill="none" d="M0 0h24v24H0z"/><path d="M12 .5l4.226 6.183 7.187 2.109-4.575 5.93.215 7.486L12 19.69l-7.053 2.518.215-7.486-4.575-5.93 7.187-2.109L12 .5zm0 3.544L9.022 8.402 3.957 9.887l3.225 4.178-.153 5.275L12 17.566l4.97 1.774-.152-5.275 3.224-4.178-5.064-1.485L12 4.044zM10 12a2 2 0 1 0 4 0h2a4 4 0 1 1-8 0h2z"/></svg>
						<span><?php _e('Reviews','wplms-app'); ?></span></span>
					</label>
				</div>
				<div class="course_block_tab_details p-6 content_bg">
					<div class="in_tab_details in_course_desc">
						<?php $p = get_post( $init->course_id ); 
								echo $p->post_content;
						?>
					</div>

					<div class="in_tab_details in_course_curriculum">
						<?php echo do_shortcode('[course_curriculum id="'.$init->course_id.'"]');?>
					</div>

					<div class="in_tab_details in_course_reviews">
						<?php 
						echo do_shortcode('[course_reviews  id="'.$init->course_id.'" hide_if_none="0" number="8" show_count="1" show_breakup="1" [/course_reviews]');
						?>
					</div>
				</div>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}


	function instructor_courses($atts,$content=null){
		$user_id = 0;
        if(!empty($atts['user_id']) && is_numeric($atts['user_id'])){
            $user_id = $atts['user_id'];
        }elseif(class_exists('VibeBP_Init')){
        	$init = VibeBP_Init::init();
        	if(!empty($init->user_id)){
        		$user_id= $init->user_id;	
        	}
        }
        if(empty($user_id) && !empty(bp_displayed_user_id())){
        	$user_id = bp_displayed_user_id();
        }

        if(empty($user_id) || !function_exists('vibebp_generate_carousel_shortcode'))
            return;

        $course_ids = bp_course_get_instructor_courses($user_id);
        
        
        $args = [
            'carousel_type'=>'post_type',
            'post_type'=>'course',
            'carousel_number'=>6,
            'post__in'=>$course_ids,
            'show_controls'=>1,
            'post_type_featured_style'=>'course2'
        ];

        $s= vibebp_generate_carousel_shortcode($args);

        $return = do_shortcode($s);
        $shortcodes = VibeBp_Shortcodes::init();
        return $return;

	}


	function my_courses($atts,$content=null){
		$user_id = 0;
        if(!empty($atts['user_id']) && is_numeric($atts['user_id'])){
            $user_id = $atts['user_id'];
        }elseif(class_exists('VibeBP_Init')){
        	$init = VibeBP_Init::init();
        	if(!empty($init->user_id)){
        		$user_id= $init->user_id;	
        	}
        }
        if(empty($user_id) && !empty(bp_displayed_user_id())){
        	$user_id = bp_displayed_user_id();
        }
        if(empty($user_id) || !function_exists('vibebp_generate_carousel_shortcode'))
            return;

        $course_ids = bp_course_get_user_courses($user_id);

        
        $args = [
            'carousel_type'=>'post_type',
            'post_type'=>'course',
            'carousel_number'=>6,
            'post__in'=>$course_ids,
            'show_controls'=>1,
            'post_type_featured_style'=>'course2'
        ];

        $s= vibebp_generate_carousel_shortcode($args);

        $return = do_shortcode($s);
        $shortcodes = VibeBp_Shortcodes::init();
        return $return;
	}
}

Wplms_App_Theme::init();