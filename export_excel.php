<?php 
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

date_default_timezone_set('Asia/Manila');
$currentMonthYear = date('F Y'); 

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Template");

$sheet->setCellValue('A1', "Sales Report for $currentMonthYear");

$sheet->setCellValue('A2', 'Business');
$sheet->setCellValue('B2', 'Branch');
$sheet->setCellValue('C2', 'Sales');
$sheet->setCellValue('D2', 'Expenses');

$sheet->mergeCells('A1:D1');
$sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="insight_report.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
