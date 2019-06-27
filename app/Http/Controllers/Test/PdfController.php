<?php
namespace App\Http\Controllers\Test;

use SnappyPdf;
//use App\Utils\PdfUtils;

/**
 * PdfController
 */
class PdfController {

    public function getPrint() {

        $pdf = SnappyPdf::loadView('Test.pdftest');
        $pdf->setOption('encoding', 'utf-8');
        return $pdf->download('pdftest.pdf');

    }

}
