<?php
if (!class_exists('OSC_PDF_Labels')) {
    class OSC_PDF_Labels {
        public function create_order_labels_pdf($orderid) {
            $uploads_dir = wp_upload_dir();
            $labels_pdf_dir = $uploads_dir['basedir'] . '/orian-labels/';
            if(!is_dir($labels_pdf_dir)) {
                mkdir($labels_pdf_dir);
            }
            $filename = $labels_pdf_dir . '/order-label-KST' . $orderid . '.pdf';
            $pdf = new PDF_Code128();
            $pdf->AddFont('Heebo','','Heebo.php');
            $pdf->AddPage('P',array(200,200));
            $pdf->SetFont('Heebo','',16);
            $text = 'עבור';
            $pdf->Code128(50,20,'KST'.$orderid,120,20);
            $pdf->Cell(0,10,iconv("UTF-8", "CP1255//TRANSLIT", $text),0,0,'R');
            $pdf->Output($filename, 'F');
        }
    }
}