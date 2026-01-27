<?php
/**
 * Initialise WPLMS Certificates
 *
 * @author 		VibeThemes
 * @category 	Admin
 * @package 	Wplms-Wishlist/Includes
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class Wplms_Pdf_Certificate_Builder_Init{

	public static $instance;
    
    public static function init(){

        if ( is_null( self::$instance ) )
            self::$instance = new Wplms_Pdf_Certificate_Builder_Init();
        return self::$instance;
    }

	private function __construct(){

		add_action( 'add_meta_boxes_certificate', array($this,'builder'));
		add_action( 'admin_enqueue_scripts', array($this,'pdf_builder_scripts'), 10, 1 );
		add_action('wp_ajax_save_pdf_builder',array($this,'save_pdf_builder'));
    }


	function builder($post_id) {
		

	    add_meta_box( 
	        'certificate-builder',__( 'PDF Certificate Builder' ),array($this,'certificate_builder'),'','normal','default');
	}

	function certificate_builder(){

		?>
		<div class="pdf_builder">
			<div class="pdf_elements">
				<div class="pdf_element text_element">
					<?php _e('Raw Text','wplms-pdf-certificates'); ?>
				</div>
				<div class="pdf_element image_element">
					<?php _e('Image','wplms-pdf-certificates'); ?>
				</div>
				<div class="pdf_element save_element button-primary">
					<?php _e('Save','wplms-pdf-certificates'); ?>
				</div>
			</div>
			<?php wp_nonce_field('save_pdf_builder','save_pdf_builder'); ?>
			<div class="pdf_builder_main">
			</div>
		</div>
		<?php

		global $post;
		$certificate_json = get_post_meta($post->ID,'certificate_json',true);
		if(!empty($certificate_json)){
			$cj = json_decode($certificate_json);
			if(!empty($cj)){
			?>
			<script>
				var certificate_json = <?php print_r($certificate_json); ?>;
			</script>
			<?php
			}
		}
		if(function_exists('vibe_get_customizer')){


		?>
		<style>
			.pdf_builder{font-size:<?php echo vibe_get_customizer('body_font_size'); ?>px !important;}
			.pdf_builder h1{font-size:<?php echo vibe_get_customizer('h1_size'); ?>px !important;padding:0 !important;}.pdf_builder h2{font-size:<?php echo vibe_get_customizer('h1_size'); ?>px !important;padding:0 !important;}
			.pdf_builder h3{font-size:<?php echo vibe_get_customizer('h3_size'); ?>px !important;padding:0 !important;}.pdf_builder h4{font-size:<?php echo vibe_get_customizer('h4_size'); ?>px !important;padding:0 !important;}
			.pdf_builder h5{font-size:<?php echo vibe_get_customizer('h5_size'); ?>px !important;}.pdf_builder h6{font-size:<?php echo vibe_get_customizer('h6_size'); ?>px !important;padding:0 !important;}
			</style>
			<?php }?>
			<script>
				
				jQuery(document).ready(function($){
					let left = $('.pdf_builder_main').offset().left;
					let top = $('.pdf_builder_main').offset().top -  $(window).scrollTop();
					jQuery(window).scroll(function(){
						left = $('.pdf_builder_main').offset().left;
						top = $('.pdf_builder_main').offset().top -  $(window).scrollTop();
					});
					$('.pdf_builder_main').append('<div class="pdf_builder_x" style="height:1px;background:red;width:100%;top:0px;left:0;position:absolute;"></div><div class="pdf_builder_y" style="width:1px;background:red;height:100%;top:0;left:0px;position:absolute;"></div>');
					$('.pdf_builder_main').on('mousemove',function(event){

						let x = event.clientY - top;
						let y = event.clientX - left;
						$('.pdf_builder_main .pdf_builder_x').css('top',x+'px');
						$('.pdf_builder_main .pdf_builder_y').css('left',y+'px');
					});
				});
			</script>
		<?php
	}

	function pdf_builder_scripts($hook){

        if ( in_array( $hook, array( 'post-new.php', 'post.php' ) ) ){
            if( (isset($_GET['post_type']) && ($_GET['post_type'] == 'certificate')) || (isset($_GET['post']) && (get_post_type($_GET['post']) == 'certificate'))){//lkj//

            	wp_enqueue_script( 'wp-embed' );
            	
                wp_enqueue_style( 'pdf_builder_css', plugins_url( '../assets/css/pdf_builder.css' , __FILE__ ),array(),rand(1,999));
                wp_enqueue_script( 'pdf_builder_script', plugins_url( '../assets/js/pdf_builder.js' , __FILE__ ),array('jquery','jquery-ui-core','jquery-ui-draggable','jquery-ui-slider'),rand(1,9999));

                global $post;
                $translations = array(
                	'id'=>$post->ID,
                	'default_text'=>_x('Default Text','pdf builder','wplms-pdf-certificates'),
                	'top_margin'=>_x('Top Margin','pdf builder','wplms-pdf-certificates'),
                	'left_margin'=>_x('Left Margin','pdf builder','wplms-pdf-certificates'),
                	'width'=>_x('Width','pdf builder','wplms-pdf-certificates'),
                	'height'=>_x('Height','pdf builder','wplms-pdf-certificates'),
                	'add_text'=>_x('Add Text','pdf builder','wplms-pdf-certificates'),
                	'edit_text'=>_x('Edit text','pdf builder','wplms-pdf-certificates'),
                	'default_image_url'=> plugins_url('../assets/images/default.png',__FILE__),
                	'add_image'=>_x('Add Image','pdf builder','wplms-pdf-certificates'),
                	'edit_image'=>_x('Edit Image','pdf builder','wplms-pdf-certificates'),
                	'remove'=>_x('Remove','pdf builder','wplms-pdf-certificates'),
                	'restore'=>_x('Restore','pdf builder','wplms-pdf-certificates'),
                	'saving'=>_x('...saving','pdf builder','wplms-pdf-certificates'),
                	'font_size'=>_x('Font Size','pdf builder','wplms-pdf-certificates'),
                	'font_color'=>_x('Font Color','pdf builder','wplms-pdf-certificates'),
                	'font_family'=>_x('Font Family','pdf builder','wplms-pdf-certificates'),
                	'font_style'=>_x('Font Style','pdf builder','wplms-pdf-certificates'),
                	'upload_image'=>_x('Upload Image','pdf builder','wplms-pdf-certificates'),
                	'border_radius'=>_x('Border Radius','pdf builder','wplms-pdf-certificates'),
                	'border_width'=>_x('Border Width','pdf builder','wplms-pdf-certificates'),
                	'text_align'=>_x('Text Align','pdf builder','wplms-pdf-certificates'),
                	'align_left'=>_x('Left Align','pdf builder','wplms-pdf-certificates'),
                	'align_right'=>_x('Right Align','pdf builder','wplms-pdf-certificates'),
                	'align_center'=>_x('Align Center','pdf builder','wplms-pdf-certificates'),
                	
                );

                $font_attachments = get_post_meta(get_the_ID(),'vibe_pdf_fonts',true);
                
                $fonts = array();
                if(!empty($font_attachments)){
                	foreach($font_attachments as $font_attachment){
                		$fonts[] = array('name'=>get_the_title($font_attachment),'path'=>get_attached_file($font_attachment));
                	}
                	$translations['fonts']=$fonts;
                }
                wp_localize_script( 'pdf_builder_script', 'pdf_builder', $translations );
            }
        }
    
	}

	function save_pdf_builder(){
		if ( !isset($_POST['security']) || !wp_verify_nonce($_POST['security'],'save_pdf_builder') || !is_user_logged_in()){
         _e('Security check Failed. Contact Administrator.','wplms-pdf-certificates');
         die();
      	}

      	if(get_post_type($_POST['id']) == 'certificate'){
      		update_post_meta($_POST['id'],'certificate_json',$_POST['certificate_json']);
      		_e('Saved','wplms-pdf-certificates');
      	}else{
      		_e('Mismatch Type','wplms-pdf-certificates');
      	}      	
      	die();
	}
}
Wplms_Pdf_Certificate_Builder_Init::init();