<?php
/**
 * Initialise WPLMS Certificates
 *
 * @author 		VibeThemes
 * @category 	Admin
 * @package 	Wplms-Pdf-Certificates/Includes
 * @version     1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class Wplms_Pdf_Certificates_Init{
	public static $instance;
    
    public static function init(){
        if ( is_null( self::$instance ) )
            self::$instance = new Wplms_Pdf_Certificates_Init();
        return self::$instance;
    }
	private function __construct(){
		add_action('wplms_certificate_earned',array($this,'certificate_earned'),999,3);


		add_action('wplms_clp_user_certificate_awarded',array($this,'wplms_clp_generate_certificate'),10,3);

		add_action('wplms_bulk_action',array($this,'bulk_action'),99,3);
		add_filter('wplms_certificate_metabox',array($this,'pdf_certificate'));
		add_filter('custom_meta_box_type',array($this,'pdf_type'),10,5);
		add_filter('wplms_certificates_pdf_args',array($this,'certificate_margins'));
		add_filter('wplms_pdf_certificate_orientation',array($this,'pdf_orientation'));
		add_filter('wplms_pdf_certificate_unit',array($this,'pdf_unit'));
		add_filter('wplms_pdf_certificate_format',array($this,'pdf_format'));
		add_action('wp_ajax_generate_pdf_certificate',array($this,'generate_pdf_certificate'));
		add_action('wp_head',array($this,'certificate_corrections'),99);
		add_action('bp_before_profile_content',array($this,'get_pdf_certificates'));
		
		add_filter('upload_mimes', array($this,'ttf_mime_types'));
		add_action('lms_general_settings',array($this,'pdf_certificate_settings'));
		add_action('init',array($this,'show_pdf_certificates'));
		$this->course_pdf_certificates = '';
		//Regenerate certificate for all users		
		add_filter('wplms_sync_settings', array($this, 'adding_regenerate_certifiacte_option'));
		add_action('wplms_sync_areas_default_action',array($this,'wplms_sync_areas_add_regenerate_certificate_action'),10,2);
		add_action('wp_ajax_sync_resync_regenerate_certificate_for_all_users',array($this,'sync_resync_regenerate_certificate_for_all_users'));
		add_action('wp_ajax_regenerate_certificate_for_all_users',array($this,'end_regenerate_certificate_for_all_users_sync'));
	}
	function adding_regenerate_certifiacte_option($settings) {
		$settings[] = array(
						'id'=>'regenerate_certificate_for_all_users',
						'label'=>__('Regenerate certificate for all users','wplms-pdf-certificates'),
						'description'=>__('Regenerate certificate for all users','wplms-pdf-certificates'),
					);
		return $settings;
	}
	function wplms_sync_areas_add_regenerate_certificate_action($action,$post){
		if($action == 'regenerate_certificate_for_all_users'){
			global $wpdb,$bp;
			$certificate_data = Array();
			$data = $wpdb->get_results("SELECT user_id, meta_value from $wpdb->usermeta WHERE meta_key = 'certificates'");
			
			if(!empty($data)){
				$security = wp_create_nonce('sync_resync_regenerate_certificate_for_all_users');
				foreach($data as $value){
					$va = unserialize($value->meta_value);
					foreach ($va as $key => $v) {
						$certificate_data[] = array('action'=>'sync_resync_regenerate_certificate_for_all_users','security'=>$security,'user_id'=>$value->user_id,'course_id'=>$v);
					}
				}
			}
			echo json_encode($certificate_data);
		}
		die();
	}
	function sync_resync_regenerate_certificate_for_all_users(){
		if ( !isset($_POST['security']) || !wp_verify_nonce($_POST['security'],'sync_resync_regenerate_certificate_for_all_users')){
	         _e('Security check Failed. Contact Administrator.','wplms-pdf-certificates');
	        die();
		}
		$user_id = $_POST['user_id'];
		if(!is_numeric($user_id))
			die();
	
		$course_id = $_POST['course_id'] ;
		if(is_numeric($course_id)){
			ob_start();
			$this->generate_certificate_pdf($course_id,'',$user_id);
			ob_end_clean();
		}
	}
	function end_regenerate_certificate_for_all_users_sync(){
		if ( !isset($_POST['security']) || !wp_verify_nonce($_POST['security'],'sync_resync')){
	         _e('Security check Failed. Contact Administrator.','wplms-pdf-certificates');
	        die();
		}
		echo __('Regenerate certificate complete !','wplms-pdf-certificates');
		die();
	}
	function get_pdf_certificates($user_id = null){
		if(empty($user_id)){
			$user_id = bp_displayed_user_id();	
		}

		if(empty($user_id) || (function_exists('bp_is_user_profile') && !bp_is_user_profile()))
			return;
		global $wpdb;

		$user = get_user_by('id',$user_id);
  		$certificate_pdf = '';

  		$display_name = sanitize_title($user->user_login).'_%'; 
  		$_certificates = $wpdb->get_results("SELECT MAX(ID) as ID,post_parent FROM {$wpdb->posts} WHERE post_type ='attachment' AND post_mime_type = 'application/pdf' AND post_name LIKE '".$display_name."' GROUP BY post_parent");


		$name = sanitize_title(bp_core_get_user_displayname($user_id)).'_%';

		$certificates = $wpdb->get_results("SELECT MAX(ID) as ID,post_parent FROM {$wpdb->posts} WHERE post_type ='attachment' AND post_mime_type = 'application/pdf' AND post_name LIKE '".$name."' GROUP BY post_parent");
		$wplms_pdf_certificates = array();

		if(!empty($certificates)){
			
			foreach($certificates as $certificate){
				$wplms_pdf_certificates[$certificate->post_parent]=wp_get_attachment_url($certificate->ID);
			}
		}
		if(!empty($_certificates)){
			foreach ($_certificates as $k => $_certificate) {
				$wplms_pdf_certificates[$_certificate->post_parent]=wp_get_attachment_url($_certificate->ID);
			}
		}

		if(!empty($wplms_pdf_certificates)){
		?>
			<script>
				var wplms_pdf_certificates = <?php echo json_encode($wplms_pdf_certificates); ?>
			</script>
			<style>#certificate img.mfp-img {padding: 0;}</style>
		<?php
		}
	}
	function certificate_corrections(){
		if(is_singular('certificate')){
			$width = get_post_meta(get_the_ID(),'vibe_certificate_width',true);
			$height = get_post_meta(get_the_ID(),'vibe_certificate_height',true);
			?>
			<style>.certificate_content{    
				background-size: 98% !important;
				background-repeat: no-repeat !important;
				width:<?php echo $width; ?>px;
				height:<?php echo $height; ?>px;
			}
			</style>
			<?php
		}
	}
	function certificate_margins($margins){
		if(!empty($this->template_id)){
			$setmargins = get_post_meta($this->template_id,'vibe_pdf_certificate_margins',true);
			if(!empty($setmargins)){
				if(is_numeric($setmargins)){
					$margins = array(
						'margin_left'=>$setmargins,
						'margin_top'=>$setmargins,
						'margin_right'=>$setmargins,
						'margin_bottom'=>$setmargins
					);
				}elseif(strpos($setmargins, ',')!== false){
					$mm = explode(',',$setmargins);
					if(is_array($mm)){
						$margins = array(
							'margin_top'=>$mm[0],
							'margin_right'=>$mm[1],
							'margin_bottom'=>$mm[2],
							'margin_left'=>$mm[3]
						);
					}
				}
			}
		}
		
		return $margins;
	}
	function pdf_orientation($orientation){
		if(!empty($this->template_id)){
			$vibe_pdf_orientation = get_post_meta($this->template_id,'vibe_pdf_orientation',true);
			if(!empty($vibe_pdf_orientation)){
				$o = explode(' ',$vibe_pdf_orientation);
				if($o[1] == 'LANDSCAPE'){
					$orientation = 'L';
					$this->orientation = $orientation;
				}
			}
			$this->w= get_post_meta($this->template_id,'vibe_certificate_width',true);
			$this->h= get_post_meta($this->template_id,'vibe_certificate_height',true);
		}
		return $orientation;
	}
	function pdf_unit($unit){
		if(!empty($this->template_id)){
			$vibe_pdf_orientation = get_post_meta($this->template_id,'vibe_pdf_orientation',true);
			if(!empty($vibe_pdf_orientation)){
				$o = explode(' ',$vibe_pdf_orientation);
				$unit = $o[0];
				$this->unit = $unit;
			}
		}
		return $unit;
	}
	function pdf_format($format){
		if(!empty($this->template_id)){
			$vibe_pdf_orientation = get_post_meta($this->template_id,'vibe_pdf_orientation',true);
			if(!empty($vibe_pdf_orientation)){
				$format = $vibe_pdf_orientation;
				$this->format = $format;
			}
		}
		return $format;
	}
	function pdf_certificate($metabox){
		$metabox[]=array( // Text Input
					'label'	=> __('PDF CERTIFICATES','wplms-pdf-certificates'), // <label>
					'desc'	=> __('Show sample certificate PDF','wplms-pdf-certificates'), // description
					'id'	=> 'vibe_certificate_pdf', // field id and name
					'type'	=> 'certificate_pdf', // type of field
				);
		$metabox[]=array( // Text Input
					'label'	=> __('PDF Margins','wplms-pdf-certificates'), // <label>
					'desc'	=> __('Add a single numerical margin on all sides -> 30 OR a comma saperated margin Top,Right,Bottom,Left -> 30,20,40,50. All margins are in milimeters','wplms-pdf-certificates'), // description
					'id'	=> 'vibe_pdf_certificate_margins', // field id and name
					'type'	=> 'text', // type of field
				);
		$metabox[]=array( // Text Input
					'label'	=> __('PDF Orientation','wplms-pdf-certificates'), // <label>
					'desc'	=> __('PDF Page orientation','wplms-pdf-certificatess'), // description
					'id'	=> 'vibe_pdf_orientation', // field id and name
					'options'=>array(
						array('label'=>__('Custom','wplms-pdf-certificates'),'value'=>''),
						array('label'=>__('A4 PORTRAIT','wplms-pdf-certificates'),'value'=>'A4 PORTRAIT'),
						array('label'=>__('A4 LANDSCAPE','wplms-pdf-certificates'),'value'=>'A4 LANDSCAPE'),
						array('label'=>__('A5 PORTRAIT','wplms-pdf-certificates'),'value'=>'A5 PORTRAIT'),
						array('label'=>__('A5 LANDSCAPE','wplms-pdf-certificates'),'value'=>'A5 LANDSCAPE'),
						array('label'=>__('A6 PORTRAIT','wplms-pdf-certificates'),'value'=>'A6 PORTRAIT'),
						array('label'=>__('A6 LANDSCAPE','wplms-pdf-certificates'),'value'=>'A6 LANDSCAPE'),
						array('label'=>__('A7 PORTRAIT','wplms-pdf-certificates'),'value'=>'A7 PORTRAIT'),
						array('label'=>__('A7 LANDSCAPE','wplms-pdf-certificates'),'value'=>'A7 LANDSCAPE'),
					),
					'type'	=> 'select', // type of field
				);
		$metabox[] =array( // Text Input
					'label'	=> __('Custom Fonts','wplms-pdf-certificates'), // <label>
					'desc'	=> __('Upload custom fonts','wplms-pdf-certificates'), // description
					'id'	=> 'vibe_pdf_fonts', // field id and name
					'type'	=> 'multiattachments', // type of field
				);
		return $metabox;
	}
	function ttf_mime_types($mimes) {
  		$mimes['ttf'] = 'application/x-font-ttf';
  		return $mimes;
	}
	function pdf_type($type,$meta,$id,$desc,$post_type){
		if($post_type == 'certificate'){
			if($type == 'certificate_pdf'){
				
				global $wpdb;
				$attachment_id = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_name = 'sample-".get_post_field('post_name',get_the_ID())."' and post_type = 'attachment' ORDER BY ID DESC limit 0,1");
				echo '<a href="'.get_permalink().'?pdf" target="_blank" class="button">'.__('View PDF HTML','wplms-pdf-certificates').'</a>&nbsp;';
				if(!empty($attachment_id)){
					echo '<a id="generate_sample_certificate" class="button-primary">'._x('Re-Generate PDF certificate',' wplms-pdf-certificates').'</a>';
					echo '&nbsp;<a href="'.wp_get_attachment_url($attachment_id).'" target="_blank" class="button" id="sample_certificate" class="button">'.__('Download PDF','wplms-pdf-certificates').'</a>';
				}else{
					echo '<a id="generate_sample_certificate" class="button-primary">'._x('Generate PDF certificate',' wplms-pdf-certificates').'</a>';
				}
				?>
				<script>
					jQuery(document).ready(function(){
					jQuery('#vibe_pdf_orientation').on('change',function(){
						
						var stored = 0;
						var params={width:0,height:0};
						if(!stored){
							stored = {width:jQuery('#vibe_certificate_width').val(),height:jQuery('#vibe_certificate_height').val()};
							params={width:stored.width,height:stored.height};
						}
						 
						switch(jQuery(this).val()){
							case 'A4 PORTRAIT':
								params={width:595,height:842};
							break;
							case 'A4 LANDSCAPE':
								params={width:842,height:595};
							break;
							case 'A5 PORTRAIT':
								params={width:630,height:892};
							break;
							case 'A5 LANDSCAPE':
								params={width:892,height:630};
							break;
							case 'A6 PORTRAIT':
								params={width:596,height:840};
							break;
							case 'A6 LANDSCAPE':
								params={width:840,height:596};
							break;
							case 'A7 PORTRAIT':
								params={width:630,height:894};
							break;
							case 'A7 LANDSCAPE':
								params={width:894,height:630};
							break;
							default:
								if(stored){
									params={width:stored.width,height:stored.height};
								}
							break;
						}
						jQuery('#vibe_certificate_width').val(params.width);
						jQuery('#vibe_certificate_height').val(params.height);
					});
					jQuery('#generate_sample_certificate').on('click',function(){
						var $ = jQuery;
						var $this = $(this);
						var dtext = $this.text();
						$this.text('<?php _e('...generating','wplms-pdf-certificates') ?>');
						jQuery('#sample_certificate').remove();
						$.ajax({
				          	type: "POST",
				          	url: ajaxurl,
				          	data: { 
				          		action: 'generate_pdf_certificate',
			          			security: '<?php echo wp_create_nonce('security'); ?>',
			                  	template_id: <?php echo get_the_ID(); ?>,
			                },
				          	cache: false,
				          	success: function (html) {
				            	var regex = /(http|https):\/\/(\w+:{0,1}\w*)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%!\-\/]))?/;
  								if(regex .test(html)) {
  									jQuery('#generate_sample_certificate').after('<a href="'+html+'" id="sample_certificate" target="_blank" class="button">&nbsp;<?php _e('Download','wplms-pdf-certificates'); ?></a>');
  								}else{
  									alert(html);
  								}
  								$this.text(dtext);
				          	}
				        });
					});
				});
				</script>
				<?php
			}
		}
		return $type;
	}
	function generate_pdf_certificate(){
		if(!current_user_can('edit_posts') || !wp_verify_nonce($_POST['security'],'security') || !is_numeric($_POST['template_id'])){
			_e('Security check failed !','wplms-pdf-certificates');
			die();
		}
		$this->template_id = $_POST['template_id'];
  		$upload_dir = wp_upload_dir();
  		$name = 'sample_'.get_post_field('post_name',$_POST['template_id']).'.pdf';
		$full_path = $upload_dir['path'].'/'.$name;
		$this->certificate_json = get_post_meta($_POST['template_id'],'certificate_json',true);
		
		if(!empty($this->certificate_json)){
			
			$query = new WP_Query(array('p'=>$_POST['template_id']));
			if($query->have_posts()){
				while($query->have_posts()){
					$query->the_post();
					global $post;
					foreach($this->certificate_json as $key=>$value){
						if(!empty($value['value'])){
							$this->certificate_json[$key]['value']=apply_filters('the_content',$value['value']);
						}
					}
				}
			}
			wp_reset_postdata();
			$bg_image_id = get_post_meta($_POST['template_id'],'vibe_background_image',true);
			
			if(is_numeric($bg_image_id)){
				$this->bg_image = get_attached_file($bg_image_id);
			}
			$attachment_id = $this->generate_json_certificate($this->certificate_json,'Sample '.get_the_title($_POST['template_id']),$full_path);
	  		if(is_numeric($attachment_id)){
	  			echo wp_get_attachment_url($attachment_id).'?'.time();
	  		}else{
	  			_e('Attachmend failed','wplms-pdf-certificates');
	  		}
			die();
		}
		$url = get_permalink($_POST['template_id']).'?pdf';
		
		$this->template_id = $_POST['template_id'];
		$response = wp_remote_get($url,array('timeout'=>30));
		if ( is_array( $response ) &&  wp_remote_retrieve_response_code( $response ) == 200) {
	  		$body =  wp_remote_retrieve_body($response);
	  		//
	  		$body = preg_replace("/<script((?:(?!src=).)*?)>(.*?)<\/script>/smix", "", $body);
	  		
	  		$body = str_replace("<a class='evolbclose '>X</a>", "", $body);
	  		
	  		
	  		$bg_image_id = get_post_meta($this->template_id,'vibe_background_image',true);
			
			if(is_numeric($bg_image_id)){
				$this->bg_image = get_attached_file($bg_image_id);
			}
			$this->user_id = get_current_user_id();
			global $wpdb;
			$old_attachment_id = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_name LIKE '%sample_".get_post_field('post_name',$this->template_id)."%' AND post_type = 'attachment' ORDER BY ID DESC limit 0,1");
			if(is_numeric($old_attachment_id)){
				wp_delete_attachment( $old_attachment_id, true );
			}
	  		$attachment_id = $this->generate_certificate($body,'Sample '.get_the_title($this->template_id),$full_path);
	  		if(is_numeric($attachment_id)){
	  			echo wp_get_attachment_url($attachment_id).'?'.time();
	  		}else{
	  			_e('Attachmend failed','wplms-pdf-certificates');
	  		}
		}else{
			_e('Failed to load Certificate','wplms-pdf-certificates');
		}
		die();
	}
	function bulk_action($action,$course_id,$members){
		if($action == 'add_certificate'){
			if(!empty($members)){
				foreach($members as $user_id){
					ob_start();
						$this->generate_certificate_pdf($course_id,'',$user_id);
					ob_end_clean();
				}
			}
		}
	}
	function certificate_earned($course_id,$pass,$user_id){
		ob_start();
			$this->generate_certificate_pdf($course_id,$pass,$user_id);
		ob_end_clean();
		$this->get_pdf_certificates($user_id);
		return;
	}

	function wplms_clp_generate_certificate($user_id,$lp_id,$template_id){
		if(!function_exists('vibe_get_option')){
			return; 
		}
		$this->bg_image = 0;
		if(empty($template_id)  || $template_id =='' ){
			return;
		}else{
			$bg_image_id = get_post_meta($template_id,'vibe_background_image',true);
			
			if(is_numeric($bg_image_id)){
				$this->bg_image = get_attached_file($bg_image_id);
			}
		}
		
		
		$this->user_id = $user_id;
		$this->course_id = $lp_id;
		$this->template_id = $template_id;
		$upload_dir = wp_upload_dir();
  		$name = $lp_id.'_'.$user_id.'.pdf';
  		$user = get_user_by('id',$user_id);
  
  		$display_name = $user->user_login.'_'.get_the_title($lp_id); 
		$full_path = $upload_dir['path'].'/'.$name;
		$this->certificate_json = get_post_meta($this->template_id,'certificate_json',true);
		
		
		if(!empty($this->certificate_json)){
			
			$this->certificate_json = json_decode($this->certificate_json,true);
			$query = new WP_Query(array('p'=>$template_id,'post_type'=>'certificate'));
			$_GET['c']=$lp_id;
			$_GET['u']=$user_id;
			
			if($query->have_posts()){
				while($query->have_posts()){
					$query->the_post();
					global $post;
					
					foreach($this->certificate_json as $key=>$value){
						if(!empty($value['value']) && $value['type'] == 'text' ){
							
							$this->certificate_json[$key]['value']=do_shortcode($value['value']);
						}
					}
				}
				
			}
			wp_reset_postdata();
			$attachment_id = $this->generate_json_certificate(json_encode($this->certificate_json),$display_name,$full_path);
	  		if(is_numeric($attachment_id)){
	  			echo wp_get_attachment_url($attachment_id);
	  		}else{
	  			_e('Attachmend failed','wplms-pdf-certificates');
	  		}
			return;
		}
		
		$url = get_permalink($template_id).'?u='.$user_id.'&c='.$lp_id.'&pdf';
		$response = wp_remote_get($url,array('timeout'=>30));
		if ( is_array( $response ) &&  wp_remote_retrieve_response_code( $response ) == 200) {
	  		$body =  wp_remote_retrieve_body($response);
	  		//
	  		$body = preg_replace("/<script((?:(?!src=).)*?)>(.*?)<\/script>/smix", "", $body);
	  		
	  		$body = str_replace("<a class='evolbclose '>X</a>", "", $body);
	  		
	  		
	  		
	  		
	  		$attachment_id = $this->generate_certificate($body,$display_name,$full_path);
	  		if(is_numeric($attachment_id)){
	  			echo wp_get_attachment_url($attachment_id);
	  		}else{
	  			_e('Attachmend failed','wplms-pdf-certificates');
	  		}
		}else{
			_e('Failed to load Certificate','wplms-pdf-certificates');
		}
		return;
	}

	function generate_certificate_pdf($course_id,$pass,$user_id){
		if(!function_exists('vibe_get_option')){
			return; 
		}
		$this->bg_image = 0;
		$template_id = get_post_meta($course_id,'vibe_certificate_template',true);
		if(empty($template_id)  || $template_id =='H' ){
			return;
		}else{
			$bg_image_id = get_post_meta($template_id,'vibe_background_image',true);
			
			if(is_numeric($bg_image_id)){
				$this->bg_image = get_attached_file($bg_image_id);
			}
		}
		
		
		$this->user_id = $user_id;
		$this->course_id = $course_id;
		$this->template_id = $template_id;
		$upload_dir = wp_upload_dir();
  		$name = $course_id.'_'.$user_id.'.pdf';
  		$user = get_user_by('id',$user_id);
  
  		$display_name = $user->user_login.'_'.get_the_title($course_id); 
		$full_path = $upload_dir['path'].'/'.$name;
		$this->certificate_json = get_post_meta($this->template_id,'certificate_json',true);
		
		
		if(!empty($this->certificate_json)){
			
			$this->certificate_json = json_decode($this->certificate_json,true);
			$query = new WP_Query(array('p'=>$template_id,'post_type'=>'certificate'));
			$_GET['c']=$course_id;
			$_GET['u']=$user_id;
			
			if($query->have_posts()){
				while($query->have_posts()){
					$query->the_post();
					global $post;
					
					foreach($this->certificate_json as $key=>$value){
						if(!empty($value['value']) && $value['type'] == 'text' ){
							
							$this->certificate_json[$key]['value']=do_shortcode($value['value']);
						}
					}
				}
				
			}
			wp_reset_postdata();
			$attachment_id = $this->generate_json_certificate(json_encode($this->certificate_json),$display_name,$full_path);
	  		if(is_numeric($attachment_id)){
	  			echo wp_get_attachment_url($attachment_id);
	  		}else{
	  			_e('Attachmend failed','wplms-pdf-certificates');
	  		}
			return;
		}
		
		$url = get_permalink($template_id).'?u='.$user_id.'&c='.$course_id.'&pdf';
		$response = wp_remote_get($url,array('timeout'=>30));
		if ( is_array( $response ) &&  wp_remote_retrieve_response_code( $response ) == 200) {
	  		$body =  wp_remote_retrieve_body($response);
	  		//
	  		$body = preg_replace("/<script((?:(?!src=).)*?)>(.*?)<\/script>/smix", "", $body);
	  		
	  		$body = str_replace("<a class='evolbclose '>X</a>", "", $body);
	  		
	  		
	  		
	  		
	  		$attachment_id = $this->generate_certificate($body,$display_name,$full_path);
	  		if(is_numeric($attachment_id)){
	  			echo wp_get_attachment_url($attachment_id);
	  		}else{
	  			_e('Attachmend failed','wplms-pdf-certificates');
	  		}
		}else{
			_e('Failed to load Certificate','wplms-pdf-certificates');
		}
		return;
	}
	function generate_certificate($html,$name,$pdf_path){
		// Include the main TCPDF library (search for installation path).
		
		require_once( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'myPDF.php' );
		
		$orientation = apply_filters('wplms_pdf_certificate_orientation',PDF_PAGE_ORIENTATION);
		$pdf_unit = apply_filters('wplms_pdf_certificate_unit',PDF_UNIT);
		$pdf_page_format = apply_filters('wplms_pdf_certificate_format',PDF_PAGE_FORMAT);
		
		if(!empty($this->bg_image)){
			// create new PDF document
			$pdf = new MYPDF($orientation, PDF_UNIT, $pdf_page_format, true, 'UTF-8', false);
		}else{
			$pdf = new TCPDF($orientation, PDF_UNIT, $pdf_page_format, true, 'UTF-8', false);
		}
		// set document information
		$pdf->SetCreator(get_bloginfo('name'));
		$pdf->SetAuthor(get_bloginfo('name'));
		$pdf->SetTitle($name);
		$pdf->SetDisplayMode('fullpage', 'SinglePage', 'UseNone');
		//$pdf->SetFont('helvetica', '', 10);
		$pdf_args = apply_filters('wplms_certificates_pdf_args',array(
			'margin_left'=>30,
			'margin_top'=>30,
			'margin_right'=>30,
			'margin_bottom'=>30
		));
		// set margins
		$pdf->SetMargins($pdf_args['margin_left'],$pdf_args['margin_top'], $pdf_args['margin_right'],true);
		// add a page
		$pdf->AddPage($orientation, $pdf_unit);
		
		
		
		do_action('wplms_generating_certificate_pdf',$pdf,$this);
		$pdf->setCellHeightRatio(1);
      
		$pdf->writeHTML($html, true, false, true, false, '');
		global $wpdb;
		$old_attachment_id = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_name LIKE '%sample_".get_post_field('post_name',$this->template_id)."%' AND post_type = 'attachment' ORDER BY ID DESC limit 0,1");
		if(is_numeric($old_attachment_id)){
			wp_delete_attachment( $old_attachment_id, true );
		}
		$name = sanitize_title($name);
		$old_at_id =$wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_name LIKE '%".$name."%' AND post_type = 'attachment' ORDER BY ID DESC limit 0,1");
		if(is_numeric($old_at_id)){
			wp_delete_attachment( $old_at_id, true );
		}
		// add a page
		ob_start();
		$pdf->Output($pdf_path, 'F');
		ob_end_clean();
		if(file_exists($pdf_path)){
			
			$filetype = wp_check_filetype( basename( $pdf_path ), null );
			$attachment = apply_filters('wplms_certificate_pdf_attachment',array(
				'guid'           => $wp_upload_dir['url'] . '/' . basename( $pdf_path ), 
				'post_mime_type' => $filetype['type'],
				'post_title'     => preg_replace( '/\.[^.]+$/', '', _x('Certificate for ','certificate title','wplms-certificates').'-'.$name ),
				'post_name'=>sanitize_title($name),
				'post_content'   => '',
				'post_author'=>$this->user_id,
				'post_status'    => 'inherit'
			));
			global $wpdb;
			
			if(!empty($this->course_id)){
				$attachment_id = wp_insert_attachment($attachment,$pdf_path,$this->course_id);	
			}else{
				$attachment_id = wp_insert_attachment($attachment,$pdf_path);
			}
	  		
	  		return $attachment_id;
		}
	}
	function generate_json_certificate($array,$name,$pdf_path){
		
		require_once( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'myPDF.php' );
		
		$orientation = apply_filters('wplms_pdf_certificate_orientation',PDF_PAGE_ORIENTATION);
		$pdf_unit = apply_filters('wplms_pdf_certificate_unit',PDF_UNIT);
		$pdf_page_format = apply_filters('wplms_pdf_certificate_format',PDF_PAGE_FORMAT);
		
		if(!empty($this->bg_image)){
			// create new PDF document
			$pdf = new MYPDF($orientation, 'pt', $pdf_page_format, true, 'UTF-8', false);
		}else{
			$pdf = new TCPDF($orientation, 'pt', $pdf_page_format, true, 'UTF-8', false);
		}
		// set document information
		$pdf->SetCreator(get_bloginfo('name'));
		$pdf->SetAuthor(get_bloginfo('name'));
		$pdf->SetTitle($name);
		$pdf->SetDisplayMode('fullpage', 'SinglePage', 'UseNone');
		//$pdf->SetFont('helvetica', '', 10);
		$pdf_args = apply_filters('wplms_certificates_pdf_args',array(
			'margin_left'=>0,
			'margin_top'=>0,
			'margin_right'=>0,
			'margin_bottom'=>0
		));
		$pdf->setCellHeightRatio(1);
		// set margins
		$pdf->SetMargins($pdf_args['margin_left'],$pdf_args['margin_top'], $pdf_args['margin_right'],true);
		// add a page
		$pdf->AddPage($orientation, $pdf_unit);
		$array = json_decode($array,true);
		
		foreach($array as $eky=>$item){
			
			if($item['type'] == 'text'){
				
				if($item['color']){
					list($r, $g, $b) = sscanf($item['color'], "#%02x%02x%02x");
					$pdf->SetTextColor($r,$g,$b);
				}
				if($item['size']){
					
					if(strpos($item['family'], '.ttf') !== false){
						//$fontfile, $type, $enc, $flags, $outpath, $platid, $encid, $addcbbox, $link
						$font_args = apply_filters('wplms_pdf_certificate_font_args',array(
							'type'=>'TrueType',
							'enc'=>'ansi',
							'flags'=>32
						),$item['family']);
						$fontname = TCPDF_FONTS::addTTFfont($item['family'], $font_args['type'], $font_args['end'], $font_args['flags']);	
						if($fontname != false){
							$item['family']=$fontname;
						}
					}
					
					if(!$item['family']){
						$item['family']='helvetica';
					}
					$pdf->SetFont ($item['family'], '', $item['size'] , '', 'default', true );	
				}
				
				
					
				if(!empty($item['align']) && $item['align'] == 'center'){
					$pdf->setXY(0,$item['top'])	;

					if($item['value'] == '[qr_code]'){
						if(!empty(vibe_get_option('certificate_page'))){
							list($r, $g, $b) = sscanf($item['color'], "#%02x%02x%02x");
							$style = array(
							    'border' => 1,
							    'vpadding' => 'auto',
							    'hpadding' => 'auto',
							    'fgcolor' => array($r,$g,$b),
							    'bgcolor' => false, 
							    'module_width' => 1,
							    'module_height' => 1
							);
							$url = get_permalink(vibe_get_option('certificate_page')).'?u='.$this->user_id.'&c='.$this->course_id;

							$pdf->write2DBarcode($url, 'QRCODE,L',$item['left'],$item['top'],$item['width'], $item['height'], $style, 'N');
						}
					}else{
						$pdf->writeHTML('<span style="text-align:center">'.$item['value'].'</span>', true, false, true, false, '');
					}
					
				}else{
					$pdf->setXY($item['left'],$item['top'])	;

					if($item['value'] == '[qr_code]'){
						
						if(!empty(vibe_get_option('certificate_page'))){
							list($r, $g, $b) = sscanf($item['color'], "#%02x%02x%02x");
							$style = array(
							    'border' => 1,
							    'vpadding' => 'auto',
							    'hpadding' => 'auto',
							    'fgcolor' => array($r,$g,$b),
							    'bgcolor' => false, 
							    'module_width' => 1,
							    'module_height' => 1
							);
							$url = get_permalink(vibe_get_option('certificate_page')).'?u='.$this->user_id.'&c='.$this->course_id;

							
							$pdf->write2DBarcode($url, 'QRCODE,L',$item['left'],$item['top'],$item['width'], $item['height'], $style, 'N');
						}
					}else{
						$pdf->writeHTML($item['value'], true, false, true, false, '');
					}
				}
						
				
				
			}
			if($item['type'] == 'image'){
				if($item['radius']){
					$pdf->StartTransform();
					$pdf->Circle($item['left'], $item['top'], $item['width'], $item['height'], 3, 0, 1, 'CNZ');
				}
				if(strpos($item['value'], home_url()) !== false){
					$item['value'] = str_replace(home_url().'/wp-content', WP_CONTENT_DIR, $item['value']);
				}
				$pdf->Image($item['value'], $item['left'], $item['top'], $item['width'], $item['height'], '', '', '', true, 300);
				if($item['radius']){
					$pdf->StopTransform();
				}
			}
		}
		global $wpdb;
		$old_attachment_id = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_name LIKE '%sample_".get_post_field('post_name',$this->template_id)."%' AND post_type = 'attachment' ORDER BY ID DESC limit 0,1");
		if(is_numeric($old_attachment_id)){
			wp_delete_attachment( $old_attachment_id, true );
		}
		$name = sanitize_title($name);
		$old_at_id =$wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_name LIKE '%".$name."%' AND post_type = 'attachment' ORDER BY ID DESC limit 0,1");
		if(is_numeric($old_at_id)){
			wp_delete_attachment( $old_at_id, true );
		}
		// add a page
		ob_start();
		$pdf->Output($pdf_path, 'F');
		ob_end_clean();
		global $wpdb;
		
		$this->user_id = get_current_user_id();
		if(file_exists($pdf_path)){
			
			$filetype = wp_check_filetype( basename( $pdf_path ), null );
			$wp_upload_dir = wp_upload_dir();
			$attachment = apply_filters('wplms_certificate_pdf_attachment',array(
				'guid'           => $wp_upload_dir['url'] . '/' . basename( $pdf_path ), 
				'post_mime_type' => $filetype['type'],
				'post_title'     => preg_replace( '/\.[^.]+$/', '', _x('Certificate for ','certificate title','wplms-certificates').'-'.$name ),
				'post_name'=>$name,
				'post_content'   => '',
				'post_author'=>$this->user_id,
				'post_status'    => 'inherit'
			));
			global $wpdb;
			
			if(!empty($this->course_id)){

				
				

				$attachment_id = wp_insert_attachment($attachment,$pdf_path,$this->course_id);	
			}else{
				$attachment_id = wp_insert_attachment($attachment,$pdf_path);
			}
	  		
	  		return $attachment_id;
		}
		
	}
	function pdf_certificate_settings($args){
		$args[]=array(
						'label' => __('PDF Certificates','wplms-pdf-certificates'),
						'name' =>'title_pdf_certificates',
						'type' => 'title',
					);
		$args[]=array(
						'label' => __('Show PDF Certificates only','wplms-pdf-certificates'),
						'name' =>'course_pdf_certificates',
						'type' => 'checkbox',
						'desc' => __('Show only PDF certificates','wplms-pdf-certificates')
					);
		return $args;
	}
	function show_pdf_certificates(){
		if(class_exists('WPLMS_tips')){
			$tips = WPLMS_tips::init();
			$this->tips = $tips->settings;
			if(!empty($tips) && !empty($tips->settings) && !empty($tips->settings['course_pdf_certificates'])){
				$this->course_pdf_certificates = $tips->settings['course_pdf_certificates'];
			}
		}
		if(function_exists('is_wplms_4_0') && is_wplms_4_0('course')){
			$this->course_pdf_certificates = 1;
		}
		if($this->course_pdf_certificates){
			add_action('wplms_validate_certificate',array($this,'check_if_pdf_certificate'),10,2);
			add_filter('bp_get_course_certificate_url',array($this,'pdf_certificates'),9,3);
			add_filter('bp_get_sample_course_certificate_url',array($this,'sample_pdf_certificates'),9,3);
		}
		
	}
	function pdf_certificates($url,$course_id,$user_id){
		global $wpdb;
		$user = get_user_by('id',$user_id);
  		$certificate_pdf = '';

  		$display_name = sanitize_title($user->user_login.'_'.get_the_title($course_id)).'%';  
  		$certificate_pdf = $wpdb->get_var("SELECT MAX(ID) FROM {$wpdb->posts} WHERE post_type ='attachment' AND post_mime_type = 'application/pdf' AND post_name LIKE '".$display_name."' ORDER BY ID DESC");


  		if(empty($certificate_pdf)){
  			$name = sanitize_title(bp_core_get_user_displayname($user_id).'_'.get_the_title($course_id)).'%';
			$certificate_pdf = $wpdb->get_var("SELECT MAX(ID) FROM {$wpdb->posts} WHERE post_type ='attachment' AND post_name LIKE '".$name."' ORDER BY ID DESC");
  		}
		if(!empty($certificate_pdf)){
			add_filter('bp_course_certificate_class',function($class){
				$class .=' pdf_view';
				return $class;
			});
			return wp_get_attachment_url($certificate_pdf).'?'.time();
		}
		return $url;
	}

	function sample_pdf_certificates($url,$course_id,$user_id=null){
		global $wpdb;
		if(!empty($user_id)){
			$name = sanitize_title(bp_core_get_user_displayname($user_id).'_'.get_the_title($course_id));
			$certificate_pdf = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_type ='attachment' AND post_name = '".$name."'");
			if(empty($certificate_pdf)){
				
				$template_id = get_post_meta($course_id,'vibe_certificate_template',true);
				$certificate_pdf =  $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_name = 'sample-".get_post_field('post_name',$template_id)."' and post_type = 'attachment' ORDER BY ID DESC limit 0,1");
			}
			if(!empty($certificate_pdf)){
				add_filter('bp_course_certificate_class',function($class){
					$class .=' pdf_view';
					return $class;
				});
				return wp_get_attachment_url($certificate_pdf).'?'.time();
			}
		}
		return $url;
	}
	function check_if_pdf_certificate($user_id,$course_id){
		if(isset($_REQUEST['certificate_code'])){
			global $wpdb;
			$user = get_user_by('id',$user_id);
	  		$certificate_pdf = '';
	  		$display_name = sanitize_title($user->user_login.'_'.get_the_title($course_id)); 
	  		$certificate_pdf = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_type ='attachment' AND post_name = '".$display_name."'");
	  		if(empty($certificate_pdf)){
	  			$name = sanitize_title(bp_core_get_user_displayname($user_id).'_'.get_the_title($course_id)).'%';
				$certificate_pdf = $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE post_type ='attachment' AND post_name LIKE '".$name."'");
	  		}
			
			if(!empty($certificate_pdf)){
				$url = wp_get_attachment_url($certificate_pdf);
				wp_redirect($url);
				exit();
			}
		}
	}
}
Wplms_Pdf_Certificates_Init::init();
