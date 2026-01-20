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


class Wplms_Gutenberg_Theme{

    public static $instance;
    
    public static function init(){

        if ( is_null( self::$instance ) )
            self::$instance = new Wplms_Gutenberg_Theme();

        return self::$instance; 
    }

    private function __construct(){


    	add_action('wplms_header_extras',[$this,'header_search']);
    	add_filter('wplms_customizer_config',[$this,'customizer']);
    	add_action('init',[$this,'footer']);
    	add_filter('wplms_site_header_style',[$this,'header_style']);
		add_filter('wplms_site_footer_style',[$this,'footer_style']);
		add_action('wp_enqueue_scripts',[$this,'scripts'],11);

		add_filter('vibebp_featured_post_type_styles_options',[$this,'wplms_vibe_course_blocks']);
		add_filter('vibebp_featured_taxonomy_styles_options',[$this,'wplms_taxonomy_blocks']);
		add_filter('vibebp_featured_members_styles_options',[$this,'wplms_member_blocks']);

		add_action('vibebp_featured_style',[$this,'wplms_vibe_course_blocks_html'],10,2);

		add_filter('vibebp_carousel_args',[$this,'carousel_args'],10,2);
		
		
		add_shortcode('instructor_courses',[$this,'instructor_courses']);
		add_shortcode('my_courses',[$this,'my_courses']);
		add_shortcode('console_text',[$this,'console_text']);
		
		add_filter('vibe_featured_thumbnail_style',[$this,'featured_thumbnail'],10,3);


		add_action('wp_enqueue_scripts',array($this,'enqueue_scripts'));
		add_action( 'after_setup_theme', [$this,'remove_widget_blocks_theme_support'] );
		
		
		add_filter('wplms_enqueue_head_gutenberg',[$this,'disable_defaults']);

		add_filter('vibe_option_custom_sections',array($this,'extend_option'));
		add_filter( 'theme_page_templates', [$this,'remove_page_templates']);

		/* == Setup Wizard === */
		add_filter('wplms_demo_styles',[$this,'demo_style']);
		add_filter('wplms_default_site_style',[$this,'default_style']);
		add_filter('wplms_required_plugins',[$this,'app_plugins']);
		add_action('wplms_envato_setup_design_save',[$this,'handle_demo_adjustments']);
		add_action('wplms_setup_wizard_page_setup',[$this,'page_setup']);
		add_filter('import_default_layouts',[$this,'no_import_course_layouts']);
		add_filter('import_important_page_layouts',[$this,'page_layouts']);
		add_filter('wplms_import_main_menu',[$this,'main_menu']);
		/* == End Setup Wizard === */

    }

    function header_search(){
    	$search = vibe_get_option('course_search');
    	if(!empty($search)){
	    	?>
	    	<div class="vibe_search border flex gap-4 items-center p-2 rounded">
	    		<form method="get">
		    		<?php
		    		if($search == 3){
		    			echo '<select name="course-cat">';
		    			$terms=  get_terms(['taxonomy'=>'course-cat','hide_empty'=>false]);
		    			if(!empty($terms)){
		    				foreach($terms as $term){
		    					echo '<option value="'.$term->slug.'">'.$term->name.'</option>';
		    				}
		    			}
		    			echo '</select>';
		    		}
		    		?>
		    		<input type="text" name="s" placeholder="<?php _e('Type to search','wplms-gutenberg'); ?>" />
		    		<?php
		    		if($search == 2){
		    			echo '<select name="course-cat">';
		    			$terms=  get_terms(['taxonomy'=>'course-cat','hide_empty'=>false]);
		    			if(!empty($terms)){
		    				foreach($terms as $term){
		    					echo '<option value="'.$term->slug.'">'.$term->name.'</option>';
		    				}
		    			}
		    			echo '</select>';
		    		}
		    		?>
		    		<input type="hidden" name="post_type" value="course" />
	    		</form>
	    		<span class="vicon vicon-search"></span>
	    	</div>
	    	<?php
	    }
    }
    function page_layouts($s){
    	unset($s[0]); // do not import default elementor based course layout
    	return $s;
    }
    function no_import_course_layouts($s){
    	return false;
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

    function featured_thumbnail($return,$custom_post,$featured_style){
    	if($featured_style == 'gutenberg'){
    		$return ='<div class="gutenberg_post_block relative flex flex-col gap-4 post_block_'.$custom_post->ID.'">';
			$return .='<div class="border rounded-xl"><a href="'.get_permalink($custom_post->ID).'">';
			if(has_post_thumbnail($custom_post->ID)){
				$return .='<img src="'.get_the_post_thumbnail_url($custom_post->ID,'medium').'" class="rounded-xl" />';	
			}else{
				$return .='<img src="'.plugins_url('../../assets/images/default.svg',__FILE__).'" class="rounded-xl" />';
			}
			
			$return .='</a></div>
			<div class="flex flex-col gap-1 flex-1">';
			$return .= '<strong class="text-xl"><a href="'.get_permalink($custom_post->ID).'">'.get_the_title($custom_post->ID).'</a></strong>';
			$return .= '<span>'.get_the_author_meta('display_name',$custom_post->post_author).'</span>';
			$return .='</div>';
			$return .= '<div class="flex gap-4 items-center justify-between">';
			if($custom_post->post_type == 'course' && function_exists('bp_course_get_course_credits')){
				$return .= '<div class="amount">'.bp_course_get_course_credits(['id'=>$custom_post->ID]).'</div>';	
			}
			$return .='<div class="post_hover_block">
			<div class="p-4 rounded-xl border-xl flex flex-col gap-2 relative">
			<h3>'.$custom_post->post_title.'</h3>
			<small class="post-taxonoomy-terms">'.get_post_modified_time('F d, Y').'</small>';

			if($custom_post->post_type == 'course' && function_exists('bp_course_get_curriculum')){
				
				$return .='<div class="flex gap-2 items-center opacity-50">
					<span class="flex items-center gap-1">
						<span class="vicon vicon-user"></span>
						<span>'.bp_course_get_students_count($custom_post->ID).'</span>
					</span>
					<span class="flex items-center gap-1">
						<span class="vicon vicon-timer"></span>
						<span>'.tofriendlytime(bp_course_get_course_duration($custom_post->ID)).'</span>
					</span>
				</div>';
				
			}

			$return .='<div class="post_intro">
			'.wp_strip_all_tags(get_the_excerpt($custom_post->ID)).'
			</div>
			<div class="post_actions flex justify-between gap-4">
			<span class="opacity-80 gap-1 flex items-center"><img class="tax_post_author" src="'.bp_core_fetch_avatar( array(
	                    'item_id' => get_the_author_meta('ID'),
	                    'type' => 'thumb',
	                    'html'    => false
	                    )).'">'.get_the_author_meta('display_name').'</span>
			<a class="link" href="'.get_permalink($custom_post->ID).'"><span class="vicon vicon-arrow-right"></span></a>
			</div>
			</div>
			</div>';
			$return .='</div></div>';

    	}

    	return $return;
    }
    
    function extend_option($sections){
    	
		$sections[1]['fields'][] = array(
					'id' => 'title_bg',
					'type' => 'text_upload',
					'title' => __('Upload Title Background', 'vibe'), 
					'sub_desc' => __('Upload a background image for title', 'vibe'),
					'desc' => __('Upload title image.', 'vibe'),
                    'std' => '#fafafa'
					);
		$sections[1]['fields'][] = array(
					'id' => 'title_color',
					'type' => 'color',
					'title' => __('Title Color', 'vibe'), 
					'sub_desc' => __('Title text/link color', 'vibe'),
					'desc' => __('Set a Title text color.', 'vibe'),
                    'std' => '#222'
					);
    	$sections[5]['fields'][]=array(
			'id' => 'default_font',
            'title' => __('Disable Default font Roboto Slab', 'vibe'),
            'sub_desc' => __('default font in Theme', 'vibe'),
            'desc' => __('Since Google fonts store data, we have included a default font in the theme.', 'vibe'),
            'type' => 'button_set',
			'options' => array(''=>__('No','vibe'),'1' => __('Yes','vibe')),//Must provide key => value pairs for radio options
			'std' => ''
		);
    	//remove redundant settings
    	unset($sections[1]['fields'][2]);
    	unset($sections[1]['fields'][6]);
    	unset($sections[1]['fields'][7]);
    	unset($sections[1]['fields'][8]);

    	unset($sections[3]['fields'][3]);
		unset($sections[3]['fields'][4]);
		unset($sections[3]['fields'][5]);
		unset($sections[3]['fields'][6]);
		unset($sections[3]['fields'][7]);
		unset($sections[3]['fields'][8]);
		unset($sections[3]['fields'][9]);
		unset($sections[3]['fields'][10]);
		unset($sections[3]['fields'][11]);
		unset($sections[3]['fields'][12]);
		unset($sections[3]['fields'][13]);
		unset($sections[3]['fields'][14]);
		unset($sections[3]['fields'][15]);
		unset($sections[3]['fields'][16]);
		unset($sections[3]['fields'][17]);
		unset($sections[3]['fields'][18]);
		unset($sections[3]['fields'][19]);
		unset($sections[3]['fields'][20]);
		unset($sections[3]['fields'][21]);
		unset($sections[3]['fields'][23]);

		unset($sections[4]['fields'][0]);
		unset($sections[4]['fields'][1]);
		unset($sections[4]['fields'][2]);
		unset($sections[4]['fields'][3]);
		unset($sections[4]['fields'][4]);
		unset($sections[4]['fields'][5]);
		unset($sections[4]['fields'][6]);
		unset($sections[4]['fields'][7]);
		unset($sections[4]['fields'][8]);

		unset($sections[4]['fields'][21]);
		unset($sections[4]['fields'][23]);
		unset($sections[4]['fields'][26]);


    	return $sections;
    }

    function disable_defaults($a){
    	return 1;
    }
    function enqueue_scripts(){
        wp_enqueue_style('vicons');
        wp_dequeue_style('wplms-style');
    }

    function page_setup(){
		if(class_exists('VibeBP_SetupWizard')){
			$wizrd = VibeBP_SetupWizard::init();
			$wizrd->import_default_xprofile(get_stylesheet_directory_uri().'/js/xprofile_fields.json');
		}
    }

    function main_menu($menu){
    	$menu = 'main';
    	return $menu;
    }
    
    function handle_demo_adjustments($demo_style=null){
		
		vibe_update_option('offload_scripts',2);
		vibe_update_option('header_extras',1);
		
		vibe_update_customizer('course_layout','blank');
		vibe_update_customizer('profile_layout','blank');
		vibe_update_customizer('group_layout','blank');
		vibe_update_customizer('directory_layout','blank');

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
    	return 'startup';
    }

    function demo_style($args){
				$args=[
					'startup'=>array(
						
						'label'=>'Startup',
						'src' => 'https://cdn.vibethemes.com/startup_demo.png',
						'version'=>'Gutenberg',
						'installation_type'=>array('instructor','mooc','academy','university'),
						'link'=>'https://startup.demos.wplms.io/',
						'plugins'=>array('vibebp','wplms_plugin','buddypress','bbpress','vibe-helpdesk','vibe-calendar','woocommerce')
					),
					'main'=>array(
						'label'=>'main',
						'src' => 'https://cdn.vibethemes.com/main_demo.jpg',
						'version'=>'Gutenberg',
						'installation_type'=>array('instructor','mooc','academy','university'),
						'link'=>'https://main.demos.wplms.io',
						'plugins'=>array('vibebp','wplms_plugin','buddypress','bbpress','vibe-helpdesk','vibe-calendar','woocommerce')
					),
					'oneinstructor'=>array(
						'label'=>'Startup',
						'src' => 'https://cdn.vibethemes.com/single_instructor.png',
						'version'=>'Gutenberg',
						'installation_type'=>array('instructor','mooc','academy','university'),
						'link'=>'https://oneinstructor.demos.wplms.io/',
						'plugins'=>array('vibebp','wplms_plugin','buddypress','bbpress','vibe-helpdesk','vibe-calendar','woocommerce')
					),
				];
	
			return $args;
		}

    function remove_widget_blocks_theme_support() {
		    remove_theme_support( 'widgets-block-editor' );
		    add_theme_support( 'custom-logo' );
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
	    $styles['gutenberg'] = 'Gutenberg Block';
	    return $styles;
	}

	function wplms_vibe_course_blocks_html($custom_post,$style){

	    if(in_array($style,['gutenberg','course','course2','course3','course4','course5'
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
	    	echo '<div class="wplms_app_tax_block course_cat '.$term->term_slug.'" style="background:'.$img.'"><a href="'.get_category_link($term->term_id ).'"><span class="flex flex-col gap-2"><strong>'.$term->name.'</strong><span class="text-sm">'.(empty($term->description)?'':substr($term->description,0,25)).'</span></span></a></div>';
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
	       
			
          $course_count = bp_course_get_instructor_course_count_for_user($post->id);
          $instructor_field = 'Current Position';
          if(vibe_get_option('instructor_field')){
          	 $instructor_field = vibe_get_option('instructor_field');
          }
          if(empty($rating)){$rating=0;}
	    
	        ?>
	        <div class="course_instructor_featured_block_wrapper member_<?php echo $post->id; ?> border rounded flex flex-col">
	            
	            <div class="member_background">
	                <div class="flex gap-4 flex-col">
	                    <a href="<?php echo $link;?>" class="flex-1 basis-12">
	                        <img src="<?php echo $avatar; ?>" alt="user profile image" class="member_avatar" />
	                    </a>
	                    <div class="flex flex-2 px-4">
	                        <a href="<?php echo $link;?>" class="flex-col flex">
	                            <strong><?php echo esc_attr( $name ); ?></strong>
	                            <span><?php echo (function_exists('xprofile_get_field_data')?xprofile_get_field_data($instructor_field,$post->id):'')?></span>
	                         </a>
	                    </div>
	                </div>
	            </div>
	            <div class="member_info flex flex-col gap-4 px-4 opacity-75">
	                <span class="flex-1">	 
	                	<span class="flex items-center gap-1"><strong style="color:#FFCB10"><?php echo empty($rating)?'N.A':round($rating,2); ?></strong><?php echo bp_course_display_rating($rating); ?><small class="opacity-70">(<?php echo $review_count; ?>)</small>               
	                </span>
	                <div class="instructor_extras">
	                    <span class="flex flex-1 justify-between">
	                    	<span class="flex gap-1 items-center">
								<span class="vicon vicon-book"></span>
								<?php echo $course_count; ?></span>
	                    	<a href="<?php echo $link;?>" class="link"><span class="vicon vicon-arrow-right"></span></a>
	                    </span>
	                </div>
	            </div>
	        </div>
	        <?php
	    }
	}

	function header_style($style){
		return '';
	}
	function footer_style($style){
		return '';
	}
	
	function customizer($args){
	    unset($args['theme']['theme_skin']);
	    //unset($args['layouts']);
	    //unset($args['header']['header_style']);
	    //unset($args['header']['header_style']);
	    return $args;
	}

	
	function footer(){
		$r = WPLMS_Actions::init();	
		remove_action('wp_footer',array($r,'search'));
		remove_action("wp_head","print_customizer_style",99);

		$actions = WPLMS_Actions::init();
    	remove_action('wplms_header_nav_search',array($actions,'nav_search'));
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

		if(empty(vibe_get_option('default_font'))){
			wp_enqueue_style('roboto_slab_font',get_stylesheet_directory_uri().'/css/default_fonts.css?2');	
		}
		
		wp_enqueue_style('wplms_app',get_stylesheet_directory_uri().'/css/app.css',[],WPLMS_GUTENBERG_VERSION);
		wp_enqueue_script('wplms_app',get_stylesheet_directory_uri().'/js/app.js',[],WPLMS_GUTENBERG_VERSION);

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
            'post_type_featured_style'=>'gutenberg'
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
            'post_type_featured_style'=>'gutenberg'
        ];

        $s= vibebp_generate_carousel_shortcode($args);

        $return = do_shortcode($s);
        $shortcodes = VibeBp_Shortcodes::init();
        return $return;
	}
	
	function console_text($atts,$content){
		$defaults=[
			'text'=>'Hello,How are you',
			'color'=>'rebeccapurple,lightblue',
		];
		$atts = wp_parse_args($atts,$defaults);
		extract($atts);

		$strings = explode(',',$text);
		$colors = explode(',',$color);

		$return = "<div class='console-container'><span id='console_text'></span><div class='console-underscore' id='console'>&#95;</div></div>";
		?>	
		<script>
			document.addEventListener('DOMContentLoaded',function(){
 			 consoleText([<?php echo '"'.implode('","',$strings).'"'; ?>], 'console_text',[<?php echo '"'.implode('","',$colors).'"'; ?>]);
			});
			function consoleText(words, id, colors) {
			  if (colors === undefined) colors = ['#fff'];
			  var visible = true;
			  var con = document.getElementById('console');
			  var letterCount = 1;
			  var x = 1;
			  var waiting = false;
			  var target = document.getElementById(id)
			  target.setAttribute('style', 'color:' + colors[0])
			  window.setInterval(function() {

			    if (letterCount === 0 && waiting === false) {
			      waiting = true;
			      target.innerHTML = words[0].substring(0, letterCount)
			      window.setTimeout(function() {
			        var usedColor = colors.shift();
			        colors.push(usedColor);
			        var usedWord = words.shift();
			        words.push(usedWord);
			        x = 1;
			        target.setAttribute('style', 'color:' + colors[0])
			        letterCount += x;
			        waiting = false;
			      }, 1000)
			    } else if (letterCount === words[0].length + 1 && waiting === false) {
			      waiting = true;
			      window.setTimeout(function() {
			        x = -1;
			        letterCount += x;
			        waiting = false;
			      }, 1000)
			    } else if (waiting === false) {
			      target.innerHTML = words[0].substring(0, letterCount)
			      letterCount += x;
			    }
			  }, 120)
			  window.setInterval(function() {
			    if (visible === true) {
			      con.className = 'console-underscore hidden'
			      visible = false;

			    } else {
			      con.className = 'console-underscore'

			      visible = true;
			    }
			  }, 400)
			}
		</script>

		<style>
		.console-container {
		  position:absolute;		  
		  margin:auto;
		}.console-underscore {
		   display:inline-block;
		  position:relative;
		  top:-0.14em;
		  left:10px;
		}</style>
		<?php
		$return .= ob_get_clean();
		return $return;
	}

}

Wplms_Gutenberg_Theme::init();