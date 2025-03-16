<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

date_default_timezone_set('Asia/Manila'); // Set timezone to Manila

require_once './conn/conn.php';
// Fetch the assigned manager's details
$manager_id = $_SESSION['user_id'];

$sql = "
    SELECT 'branch' AS type, b.id, b.location AS name, b.business_id, bs.name AS business_name
    FROM branch b
    LEFT JOIN business bs ON b.business_id = bs.id
    WHERE b.manager_id = ?
    UNION
    SELECT 'business' AS type, id, name, NULL AS business_id, NULL AS business_name
    FROM business
    WHERE manager_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $manager_id, $manager_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $assigned = $result->fetch_assoc();

    // Determine the type and details of the assignment
    if ($assigned['type'] === 'branch') {
        $assigned_type = 'Branch';
        $assigned_name = $assigned['name'];
        $business_id = $assigned['business_id'];
        $business_name = $assigned['business_name'];
    } else {
        $assigned_type = 'Business';
        $assigned_name = $assigned['name'];
        $business_id = null;
        $business_name = null;
    }
} else {
    // No assignment found
    $assigned_type = null;
    $assigned_name = null;
    $business_id = null;
    $business_name = null;
}

// Get selected values from request
$productId = $_POST['product_id'] ?? 'N/A';
$selectedProduct = $_POST['selectedProduct'] ?? 'No Product Selected';
$productPrice = $_POST['productPrice'] ?? 0;
$dateToday = date('Y-m-d'); // Now using Asia/Manila timezone

// Initialize Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set headers based on assigned type
if ($assigned_type === 'Branch') {
    $sheet->setCellValue('A1', 'Business Name')->getStyle('A1')->getFont()->setBold(true);
    $sheet->setCellValue('B1', "ID:$business_id - $business_name");
    $sheet->setCellValue('A2', 'Branch')->getStyle('A2')->getFont()->setBold(true);
    $sheet->setCellValue('B2', "ID:{$assigned['id']} - $assigned_name");
} elseif ($assigned_type === 'Business') {
    $sheet->setCellValue('A1', 'Business Name')->getStyle('A1')->getFont()->setBold(true);
    $sheet->setCellValue('B1', "ID:{$assigned['id']} - $assigned_name");
    $sheet->setCellValue('A2', 'Branch')->getStyle('A2')->getFont()->setBold(true);
    $sheet->setCellValue('B2', "Main Branch");
}

// Product and Price
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