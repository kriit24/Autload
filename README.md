# Autoload
New composer Autoloader

<?php

set_include_path(__DIR__);
require_once __DIR__ . '/testAutoload.php';
new Autoload(__DIR__ . '/application');

\Autoload::alias('PhpOffice', 'application/Package/PhpOffice');
\Autoload::alias('Psr', 'application/Package/vendor/psr/simple-cache/src');
\Autoload::alias('ZipStream', 'application/Package/vendor/maennchen/zipstream-php/src');
\Autoload::alias('MyCLabs', 'application/Package/vendor/myclabs/php-enum/src');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx AS Writer_Xlsx;
use PhpOffice\PhpSpreadsheet\Style;

$objPHPExcel = new Spreadsheet();
$objPHPExcel->getActiveSheet()->SetCellValue('A1', 'Hello World');

$styleArray = [
    'borders' => [
        'allborders' => [
            'style' => Style\Border::BORDER_DASHED
        ]
    ]
];

$objPHPExcel
    ->getActiveSheet()
    ->getStyle('A1')
    ->applyFromArray($styleArray);

$objWriter = new Writer_Xlsx($objPHPExcel);
$file = uniqid() . '.xlsx';
$objWriter->save($file);


$len = filesize($file);
if (!$fileName) {
    $fileName = basename($file);
}

//Begin writing headers
header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: public");
header("Content-Description: File Transfer");

//Use the switch-generated Content-Type
header("Content-Type: application/vnd.ms-excel");

//Force the download
$header = "Content-Disposition: attachment; filename=" . $fileName . ";";
header($header);
header("Content-Transfer-Encoding: binary");
header("Content-Length: " . $len);
@readfile($file);
