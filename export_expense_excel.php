<?php
session_start();
require 'vendor/autoload.php';
require_once './conn/conn.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

date_default_timezone_set('Asia/Manila');

$owner_id = $_SESSION['user_id'];

// Fetch Business Data
$businesses = [];
$sql = "SELECT id, name FROM business WHERE owner_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $businesses[] = $row;
}
$stmt->close();

// Fetch Branch Data
$branches = [];
$sql = "SELECT id, location, business_id FROM branch WHERE business_id IN (SELECT id FROM business WHERE owner_id = ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $branches[] = $row;
}
$stmt->close();

// Fetch Expense Types Data
$expense_types = [];
$sql = "SELECT id, type_name FROM expense_type WHERE is_custom = 0 OR (is_custom = 1 AND owner_id = ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $expense_types[] = $row;
}
$stmt->close();

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Expense Template");

$boldStyle = [
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
];

// Add Business Data
$sheet->setCellValue('A1', 'Business')->getStyle('A1')->applyFromArray($boldStyle);
$rowNum = 2;
foreach ($businesses as $business) {
    $sheet->setCellValue("A$rowNum", "{$business['id']} - {$business['name']}");
    $rowNum++;
}

// Add Branch Data
$sheet->setCellValue('B1', 'Branch/Branches')->getStyle('B1')->applyFromArray($boldStyle);
$rowNum = 2;
foreach ($branches as $branch) {
    $sheet->setCellValue("B$rowNum", "{$branch['id']} - {$branch['location']}");
    $rowNum++;
}

// Add Expense Types Data
$sheet->setCellValue('C1', 'Expense Types')->getStyle('C1')->applyFromArray($boldStyle);
$rowNum = 2;
foreach ($expense_types as $expense) {
    $sheet->setCellValue("C$rowNum", "{$expense['id']} - {$expense['type_name']}");
    $rowNum++;
}

// Calculate the maximum number of rows used by Business, Branch, and Expense Types
$maxRows = max(count($businesses), count($branches), count($expense_types)) + 2; // +2 for headers and 1-based indexing

// Add the "Expenses Report" header
$sheet->setCellValue("A$maxRows", 'Expenses Report')->getStyle("A$maxRows")->applyFromArray($boldStyle);

// Add the headers for the expenses table
$headers = ['Expense Type', 'Amount', 'Category', 'Business', 'Branch', 'Description', 'Date'];
$colIndex = 'A';
$headerRow = $maxRows + 1; // Place headers in the row after the "Expenses Report" header
foreach ($headers as $header) {
    $cell = $colIndex . $headerRow;
    $sheet->setCellValue($cell, $header);
    $sheet->getStyle($cell)->applyFromArray($boldStyle);
    $colIndex++;
}

// Add data validation for Business, Branch, and Expense Types
$validation = $sheet->getCell('D' . ($headerRow + 1))->getDataValidation();
$validation->setType(DataValidation::TYPE_LIST);
$validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
$validation->setAllowBlank(false);
$validation->setShowInputMessage(true);
$validation->setShowErrorMessage(true);
$validation->setShowDropDown(true);
$validation->setFormula1('$A$2:$A$' . ($maxRows - 1)); // Business data range

for ($i = $headerRow + 1; $i <= $headerRow + 10; $i++) {
    $sheet->getCell("D$i")->setDataValidation(clone $validation);
}

$validation = $sheet->getCell('E' . ($headerRow + 1))->getDataValidation();
$validation->setType(DataValidation::TYPE_LIST);
$validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
$validation->setAllowBlank(false);
$validation->setShowInputMessage(true);
$validation->setShowErrorMessage(true);
$validation->setShowDropDown(true);
$validation->setFormula1('$B$2:$B$' . ($maxRows - 1)); // Branch data range

for ($i = $headerRow + 1; $i <= $headerRow + 10; $i++) {
    $sheet->getCell("E$i")->setDataValidation(clone $validation);
}

$validation = $sheet->getCell('A' . ($headerRow + 1))->getDataValidation();
$validation->setType(DataValidation::TYPE_LIST);
$validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
$validation->setAllowBlank(false);
$validation->setShowInputMessage(true);
$validation->setShowErrorMessage(true);
$validation->setShowDropDown(true);
$validation->setFormula1('$C$2:$C$' . ($maxRows - 1)); // Expense Types data range

for ($i = $headerRow + 1; $i <= $headerRow + 10; $i++) {
    $sheet->getCell("A$i")->setDataValidation(clone $validation);
}

// Add data validation for Category
$categoryList = 'Business,Branch';
$validation = $sheet->getCell('C' . ($headerRow + 1))->getDataValidation();
$validation->setType(DataValidation::TYPE_LIST);
$validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
$validation->setAllowBlank(false);
$validation->setShowInputMessage(true);
$validation->setShowErrorMessage(true);
$validation->setShowDropDown(true);
$validation->setFormula1('"' . $categoryList . '"');

for ($i = $headerRow + 1; $i <= $headerRow + 10; $i++) {
    $sheet->getCell("C$i")->setDataValidation(clone $validation);
}

// Add default values for Amount, Description, and Date
for ($i = $headerRow + 1; $i <= $headerRow + 10; $i++) {
    $sheet->setCellValue("B$i", "0");
    $sheet->setCellValue("F$i", "");
    $sheet->setCellValue("G$i", date('m/d/Y'));
}

// Set fixed column width
$fixedWidth = 20;
foreach (range('A', 'G') as $col) {
    $sheet->getColumnDimension($col)->setWidth($fixedWidth);
}

// Output the file
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Expense_Template.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>