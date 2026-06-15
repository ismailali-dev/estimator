<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require __DIR__.'/../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setTitle('Sheet 1'); // This is where you set the title
$sheet->setCellValue('A1', 'Category');
$sheet->setCellValue('B1', 'Supplier');
$sheet->setCellValue('C1', 'No. of Codes');
$sheet->setCellValue('D1', 'Allowance');
$sheet->setCellValue('E1', 'Total');
$sheet->setCellValue('F1', 'Difference');



$row = 2;// Initialize row counter

// This is the loop to populate data
for ($i=1; $i < 5; $i++) {
    $sheet->setCellValue('A' . $row, $i);
    $sheet->setCellValue('B' . $row, "People ".$i);
    $row++;

}
$writer = new Xlsx($spreadsheet);
$fileName = "Your First Excel Exported From Laravel.xlsx";
header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header("Content-Disposition: attachment;filename=\"$fileName\"");
$writer->save("php://output");
exit();
?>
