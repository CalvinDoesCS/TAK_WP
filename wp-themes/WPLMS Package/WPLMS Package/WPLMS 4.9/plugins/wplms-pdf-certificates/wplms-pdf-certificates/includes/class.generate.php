<?php
/**
 * Generate WPLMS Certificates
 *
 * @author 		VibeThemes
 * @category 	Admin
 * @package 	Wplms-Wishlist/Includes
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class Wplms_Pdf_Certificates_Generate{

	public static $instance;
    
    public static function init(){

        if ( is_null( self::$instance ) )
            self::$instance = new Wplms_Pdf_Certificates_Generate();
        return self::$instance;
    }

	private function __construct(){
		add_filter('the_content',array($this,'certificate_builder'));
	}


	function certificate_builder($content){
		global $post;
		if(empty($post)|| empty($post->post_type) || $post->post_type != 'certificate')
			return $content;

		$certificate_json = get_post_meta($post->ID,'certificate_json',true);
		if(empty($certificate_json))
			return $content;

		$certificate_json = json_decode($certificate_json);
		if(!is_array($certificate_json) || !count($certificate_json))
			return $content;


		$content = '';
		foreach($certificate_json as $json){
			if($json->type == 'text'){
				$content .= '<div class="inline_text" style="position:absolute;top:'.$json->top.'px;left:'.$json->left.'px;width:'.$json->width.'px;height:'.$json->height.'px;color:'.$json->color.';font-family:'.$json->family.';font-size:'.$json->size.'px;text-align:'.(($json->align)?$json->align:'start').';">'.do_shortcode($json->value).'</div>';
			}

			if($json->type == 'image'){
				$content .= '<div class="inline_image" style="position:absolute;top:'.$json->top.'px;left:'.$json->left.'px;width:'.$json->width.'px;height:'.$json->height.'px;border-radius:'.$json->radius.'px;"><img src="'.do_shortcode($json->value).'" /></div>';
			}
		}
		$width = get_post_meta(get_the_ID(),'vibe_certificate_width',true);
		$height = get_post_meta(get_the_ID(),'vibe_certificate_height',true);
		$content .= '<style>.certificate_content{background-size: cover !important;min-width:'.$width.'px;min-height:'.$height.'px;#certificate .col-md-12{padding:0;}}</style>';


		return $content;
	}
}

Wplms_Pdf_Certificates_Generate::init();