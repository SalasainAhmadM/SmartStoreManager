<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

date_default_timezone_set('Asia/Manila'); // Set timezone to Manila

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Get selected values from request
$businessId = $_POST['business_id'] ?? 'N/A';
$selectedBusiness = $_POST['selectedBusiness'] ?? 'No Business Selected';
$branchId = $_POST['branch_id'] ?? 'N/A';
$selectedBranch = $_POST['selectedBranch'] ?? 'No Branch Selected';
$productId = $_POST['product_id'] ?? 'N/A';
$selectedProduct = $_POST['selectedProduct'] ?? 'No Product Selected';
$productPrice = $_POST['productPrice'] ?? 0;
$dateToday = date('Y-m-d'); // Now using Asia/Manila timezone

// Set headers
$sheet->setCellValue('A1', 'Business Name')->getStyle('A1')->getFont()->setBold(true);
$sheet->setCellValue('B1', "ID:$businessId - $selectedBusiness");
$sheet->setCellValue('A2', 'Branch')->getStyle('A2')->getFont()->setBold(true);
$sheet->setCellValue('B2', "ID:$branchId - $selectedBranch");
$sheet->setCellValue('A3', 'Product')->getStyle('A3')->getFont()->setBold(true);
$sheet->setCellValue('B3', "ID:$productId - $selectedProduct");
$sheet->setCellValue('A4', 'Price')->getStyle('A4')->getFont()->setBold(true);
$sheet->setCellValue('B4', $productPrice);

// Sales Header
$sheet->setCellValue('B6', 'Sales Report')->getStyle('B6')->getFont()->setBold(true);

// Table Headers
$sheet->setCellValue('A7', 'Amount Sold')->getStyle('A7')->getFont()->setBold(true);
$sheet->setCellValue('B7', 'Total Sales')->getStyle('B7')->getFont()->setBold(true);
$sheet->setCellValue('C7', 'Date')->getStyle('C7')->getFont()->setBold(true);

// Default Data: Amount Sold = 0, Auto-calculated Total Sales
for ($row = 8; $row <= 15; $row++) {
    $sheet->setCellValue('A' . $row, 0); // Default Amount Sold to 0
    $sheet->setCellValue('B' . $row, "=A$row * B4"); // Auto-calculate Total Sales
    $sheet->setCellValue('C' . $row, $dateToday); // Set current date
}

// Set column width for better readability
foreach (range('A', 'C') as $col) {
    $spreadsheet->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
}

// Output file
$filename = 'Sales_Report_' . $dateToday . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
