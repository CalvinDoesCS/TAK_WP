<?php

require_once( trailingslashit( plugin_dir_path( __FILE__ ) ) . '../tcpdf/tcpdf.php' );

class MYPDF extends TCPDF {

    //Page header
    public function Header() {
        // get the current page break margin
        $bMargin = $this->getBreakMargin();
        // get current auto-page-break mode
        $auto_page_break = $this->AutoPageBreak;
        // disable auto-page-break
        $this->SetAutoPageBreak(false, 0);
        // set bacground image
        $init = Wplms_Pdf_Certificates_Init::init();
        $img_file = $init->bg_image;

        $dimension = array(
            'A0'=>array('w'=>2626,'h'=>2626),
            'A1'=>array('w'=>1437,'h'=>2626),
            'A2'=>array('w'=>1189,'h'=>1437),
            'A3'=>array('w'=>842,'h'=>1189),
            'A4'=>array('w'=>595,'h'=>842),
            'A5'=>array('w'=>297,'h'=>420),
            'A6'=>array('w'=>210,'h'=>295),
            'A7'=>array('w'=>74,'h'=>105),
        );
        
        if(!empty($init->unit) && !empty($dimension[$init->unit])){

            $w = $dimension[$init->unit]['w'];
            $h = $dimension[$init->unit]['h'];
        }
        if(!empty($init->orientation) && $init->orientation == 'L'){
            $t=$w;
            $w = $h;
            $h=$t;
        }
        if(empty($init->orientation)){
            $w = $init->w;
            $h = $init->h;
        }

        $this->Image($img_file, 0, 0, $w, $h, '', '', '', false, 300, '', false, false, 0);
        // restore auto-page-break status
        $this->SetAutoPageBreak(0, 0);
        // set the starting point for the page content
        $this->setPageMark();
    }
}
?>