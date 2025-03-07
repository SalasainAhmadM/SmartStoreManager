<?php
session_start();
require 'vendor/autoload.php';
require_once './conn/conn.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

date_default_timezone_set('Asia/Manila');

$owner_id = $_SESSION['user_id'];

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

$sheet->setCellValue('A1', 'Business')->getStyle('A1')->applyFromArray($boldStyle);
$sheet->setCellValue('B1', 'Branch/Branches')->getStyle('B1')->applyFromArray($boldStyle);
$sheet->setCellValue('C1', 'Expense Types')->getStyle('C1')->applyFromArray($boldStyle);

$rowNum = 2;
foreach ($businesses as $business) {
    $sheet->setCellValue("A$rowNum", "{$business['id']} - {$business['name']}");
    $rowNum++;
}

$rowNum = 2;
foreach ($branches as $branch) {
    $sheet->setCellValue("B$rowNum", "{$branch['id']} - {$branch['location']}");
    $rowNum++;
}

$rowNum = 2;
foreach ($expense_types as $expense) {
    $sheet->setCellValue("C$rowNum", "{$expense['id']} - {$expense['type_name']}");
    $rowNum++;
}

$sheet->setCellValue('A14', 'Expenses Report')->getStyle('A14')->applyFromArray($boldStyle);

$headers = ['Expense Type', 'Amount', 'Category', 'Business', 'Branch', 'Description', 'Date'];
$colIndex = 'A';
foreach ($headers as $header) {
    $cell = $colIndex . "15";
    $sheet->setCellValue($cell, $header);
    $sheet->getStyle($cell)->applyFromArray($boldStyle);
    $colIndex++;
}

$validation = $sheet->getCell('D16')->getDataValidation();
$validation->setType(DataValidation::TYPE_LIST);
$validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
$validation->setAllowBlank(false);
$validation->setShowInputMessage(true);
$validation->setShowErrorMessage(true);
$validation->setShowDropDown(true);
$validation->setFormula1('$A$2:$A$13');

for ($i = 16; $i <= 24; $i++) {
    $sheet->getCell("D$i")->setDataValidation(clone $validation);
}

$validation = $sheet->getCell('E16')->getDataValidation();
$validation->setType(DataValidation::TYPE_LIST);
$validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
$validation->setAllowBlank(false);
$validation->setShowInputMessage(true);
$validation->setShowErrorMessage(true);
$validation->setShowDropDown(true);
$validation->setFormula1('$B$2:$B$13');

for ($i = 16; $i <= 24; $i++) {
    $sheet->getCell("E$i")->setDataValidation(clone $validation);
}

$validation = $sheet->getCell('A16')->getDataValidation();
$validation->setType(DataValidation::TYPE_LIST);
$validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
$validation->setAllowBlank(false);
$validation->setShowInputMessage(true);
$validation->setShowErrorMessage(true);
$validation->setShowDropDown(true);
$validation->setFormula1('$C$2:$C$13');

for ($i = 16; $i <= 24; $i++) {
    $sheet->getCell("A$i")->setDataValidation(clone $validation);
}

$branchList = implode(',', array_map(function ($branch) {
    return "{$branch['id']} - {$branch['location']}";
}, $branches));

$validation = $sheet->getCell('E16')->getDataValidation();
$validation->setType(DataValidation::TYPE_LIST);
$validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
$validation->setAllowBlank(false);
$validation->setShowInputMessage(true);
$validation->setShowErrorMessage(true);
$validation->setShowDropDown(true);
$validation->setFormula1('"' . $branchList . '"');

for ($i = 16; $i <= 24; $i++) {
    $sheet->getCell("E$i")->setDataValidation(clone $validation);
}

$categoryList = 'Business,Branch';
$validation = $sheet->getCell('C16')->getDataValidation();
$validation->setType(DataValidation::TYPE_LIST);
$validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
$validation->setAllowBlank(false);
$validation->setShowInputMessage(true);
$validation->setShowErrorMessage(true);
$validation->setShowDropDown(true);
$validation->setFormula1('"' . $categoryList . '"');

for ($i = 16; $i <= 24; $i++) {
    $sheet->getCell("C$i")->setDataValidation(clone $validation);
}

for ($i = 16; $i <= 24; $i++) {
    $sheet->setCellValue("B$i", "0");
    $sheet->setCellValue("F$i", "");
    $sheet->setCellValue("G$i", date('m/d/Y'));
}

$fixedWidth = 20;
foreach (range('A', 'F') as $col) {
    $sheet->getColumnDimension($col)->setWidth($fixedWidth);
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="expense_template.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>