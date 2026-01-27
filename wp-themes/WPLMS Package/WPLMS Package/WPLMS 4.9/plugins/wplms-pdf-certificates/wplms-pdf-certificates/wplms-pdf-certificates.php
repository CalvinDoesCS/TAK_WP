<?php
/*
Plugin Name: WPLMS PDF Certificates
Plugin URI: https://wplms.io
Description: PDF certificates for WPLMS
Author: VibeThemes
Version: 2.7
Author URI: https://www.wplms.io
Text Domain: wplms-pdf-certificates
Domain Path: /languages/
*/

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WPLMS_PDF_CERTIFICATES_UPDATE_ITEM_ID', 20204 );

define( 'WPLMS_PDF_CERTIFICATES_VERSION', '2.7' );
require_once( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'includes/class.config.php' );
require_once( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'includes/class.updater.php' );

require_once( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'includes/class.init.php' );
require_once( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'includes/class.builder.php' );
require_once( trailingslashit( plugin_dir_path( __FILE__ ) ) . 'includes/class.generate.php' );

add_action('plugins_loaded','wplms_pdf_certificates_translations');
function wplms_pdf_certificates_translations(){
    $locale = apply_filters("plugin_locale", get_locale(), 'wplms-pdf-certificates');
    $lang_dir = dirname( __FILE__ ) . '/languages/';
    $mofile        = sprintf( '%1$s-%2$s.mo', 'wplms-pdf-certificates', $locale );
    $mofile_local  = $lang_dir . $mofile;
    $mofile_global = WP_LANG_DIR . '/plugins/' . $mofile;

    if ( file_exists( $mofile_global ) ) {
        load_textdomain( 'wplms-pdf-certificates', $mofile_global );
    } else {
        load_textdomain( 'wplms-pdf-certificates', $mofile_local );
    }  
}


function Wplms_Pdf_Certificates_Plugin_updater() {
    $license_key = trim( get_option( 'wplms_pdf_certificates_license_key' ) );
    $edd_updater = new Wplms_Pdf_Certificates_Plugin_Updater( 'https://wplms.io', __FILE__, array(
            'version'   => WPLMS_PDF_CERTIFICATES_VERSION,               
            'license'   => $license_key,        
            'item_id'=>WPLMS_PDF_CERTIFICATES_UPDATE_ITEM_ID,  
            'author'    => 'VibeThemes' 
        )
    );
}
add_action( 'admin_init', 'Wplms_Pdf_Certificates_Plugin_updater', 0 );
