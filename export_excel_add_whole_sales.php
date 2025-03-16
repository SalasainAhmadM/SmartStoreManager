<?php
session_start();
require_once './conn/auth.php';
require_once './conn/conn.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

validateSession('owner');

$owner_id = $_SESSION['user_id'];
$selected_business_id = $_POST['business_id'] ?? null;

if (!$selected_business_id) {
    die("No business selected.");
}

// Fetch business
$query = "SELECT id, name FROM business WHERE id = ? AND owner_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $selected_business_id, $owner_id);
$stmt->execute();
$business_result = $stmt->get_result();
if ($business_result->num_rows === 0) {
    die("Invalid business selected.");
}
$business = $business_result->fetch_assoc();
$stmt->close();

// Fetch branches
$query = "SELECT id, location FROM branch WHERE business_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $selected_business_id);
$stmt->execute();
$branch_result = $stmt->get_result();

$branches = [];
while ($row = $branch_result->fetch_assoc()) {
    $branches[$row['id']] = "ID:{$row['id']} - {$row['location']}";
}
$stmt->close();

// Fetch products
$query = "SELECT id, name, price FROM products WHERE business_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $selected_business_id);
$stmt->execute();
$product_result = $stmt->get_result();

$products = [];
while ($row = $product_result->fetch_assoc()) {
    $products[$row['id']] = $row;
}
$stmt->close();

// Create Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
date_default_timezone_set('Asia/Manila');
$today = date("Y-m-d");

// Set headers
$sheet->setCellValue('A1', 'Business Name')->getStyle('A1')->getFont()->setBold(true);
$sheet->setCellValue('B1', "ID:{$business['id']} - {$business['name']}");

$sheet->setCellValue('A2', 'Branch/Branches')->getStyle('A2')->getFont()->setBold(true);
$row = 3;
foreach ($branches as $branch_text) {
    $sheet->setCellValue('B' . $row, $branch_text);
    $row++;
}

// Sales Report Header
$sheet->setCellValue('A' . ($row + 1), 'Sales Report')->getStyle('A' . ($row + 1))->getFont()->setBold(true);

// Table Headers (Bold)
$headers = ['Products', 'Price', 'Amount Sold', 'Total Sales', 'Date', 'Business/Branch'];
$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . ($row + 2), $header);
    $sheet->getStyle($col . ($row + 2))->getFont()->setBold(true);
    $col++;
}

$row += 3;

// Populate Products and Add Dropdown for Business/Branch
foreach ($products as $product) {
    $product_name = "ID:{$product['id']} - {$product['name']}";
    $sheet->setCellValue('A' . $row, $product_name);
    $sheet->setCellValue('B' . $row, $product['price']);
    $sheet->setCellValue('C' . $row, 0);
    $sheet->setCellValue('D' . $row, "=B$row * C$row");
    $sheet->setCellValue('E' . $row, $today);
    $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
    $sheet->setCellValue('F' . $row, '');

    // Add Data Validation for Column F (Branch Selection)
    $validation = $sheet->getCell('F' . $row)->getDataValidation();
    $validation->setType(DataValidation::TYPE_LIST);
    $validation->setErrorStyle(DataValidation::STYLE_STOP);
    $validation->setAllowBlank(false);
    $validation->setShowInputMessage(true);
    $validation->setShowErrorMessage(true);
    $validation->setErrorTitle('Invalid Selection');
    $validation->setError('Please select a valid branch from the list.');
    $validation->setShowDropDown(true);
    $validation->setFormula1('"' . implode(',', $branches) . '"');

    $row++;
}

// Set Fixed Column Widths
$sheet->getColumnDimension('A')->setWidth(30);
$sheet->getColumnDimension('B')->setWidth(15);
$sheet->getColumnDimension('C')->setWidth(12);
$sheet->getColumnDimension('D')->setWidth(15);
$sheet->getColumnDimension('E')->setWidth(15);
$sheet->getColumnDimension('F')->setWidth(30);

// Output File
$clean_business_name = str_replace(' ', '', $business['name']);
$filename = "{$clean_business_name}_Sales_Report_Template_{$today}.xlsx";


header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>