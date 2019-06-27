<?php
namespace App\Utils;

use \Knp\Snappy\Pdf;

/**
 * PdfUtils
 */
class PdfUtils extends Pdf {

    // 初期値にwkhtmltopdfの位置を登録、エンコード設定をUTF-8に変更
    public function __construct($binary = '/usr/local/bin/wkhtmltopdf', array $options = [], array $env = null)
    {
        parent::__construct($binary, $options, $env);
        $this->setOption('encoding', 'utf-8');
    }

    public function setTemplate($templatePath)
    {
        $this->loadView($templatePath);
    }

    public function downloadPdf($fileName)
    {
        return $this->download($fileName);
    }

    // headerの出力も自動で行う
    public function output($html)
    {
        header("Content-Type: application/pdf");
        return $this->getOutputFromHtml($html);
    }

}

