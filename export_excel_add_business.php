<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Business Template");

// Business Information Headers
$sheet->setCellValue('A1', 'Business Name');
$sheet->setCellValue('B1', 'Business Description');
$sheet->setCellValue('C1', 'Asset Size');
$sheet->setCellValue('D1', 'Number of Employees');
$sheet->setCellValue('E1', 'Location');

// Leave row 2 empty for business information input
$sheet->setCellValue('A2', '');
$sheet->setCellValue('B2', '');
$sheet->setCellValue('C2', '');
$sheet->setCellValue('D2', '');

// Products Section Header
$sheet->mergeCells('A3:D3');
$sheet->setCellValue('A3', 'Products');
$sheet->getStyle('A3')->getAlignment()->setHorizontal('center');

// Product Information Headers
$sheet->setCellValue('A4', 'Name');
$sheet->setCellValue('B4', 'Type');
$sheet->setCellValue('C4', 'Price');
$sheet->setCellValue('D4', 'Size');
$sheet->setCellValue('E4', 'Description');

// Branches Section
$sheet->mergeCells('A15:D15');
$sheet->setCellValue('A15', 'Branch/Branches');
$sheet->getStyle('A15')->getAlignment()->setHorizontal('left');
$sheet->getStyle('A15')->applyFromArray(['font' => ['bold' => true]]);

// Locations Section
$sheet->setCellValue('A16', 'Locations');

// Style headers
$headerStyle = [
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT]
];
$sheet->getStyle('A1:E1')->applyFromArray($headerStyle);
$sheet->getStyle('A3:D3')->applyFromArray($headerStyle);
$sheet->getStyle('A4:E4')->applyFromArray($headerStyle);
$sheet->getStyle('A15')->applyFromArray($headerStyle);
$sheet->getStyle('A16')->applyFromArray($headerStyle);

// Auto-size columns
foreach (range('A', 'E') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Business_Template.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>