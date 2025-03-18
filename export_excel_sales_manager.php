<?php
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

session_start();
require './conn/conn.php';

$manager_id = $_SESSION['user_id'];

$business = null;
$branch = null;

$sqlBusiness = "SELECT * FROM business WHERE manager_id = ?";
$stmt = $conn->prepare($sqlBusiness);
$stmt->bind_param("i", $manager_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $business = $result->fetch_assoc();
} else {
    $sqlBranch = "SELECT * FROM branch WHERE manager_id = ?";
    $stmt = $conn->prepare($sqlBranch);
    $stmt->bind_param("i", $manager_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $branch = $result->fetch_assoc();
        $sqlBusinessFromBranch = "SELECT * FROM business WHERE id = ?";
        $stmt = $conn->prepare($sqlBusinessFromBranch);
        $stmt->bind_param("i", $branch['business_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $business = $result->fetch_assoc();
        }
    }
}

if (!$business && !$branch) {
    die("No business or branch assigned to this manager.");
}

$business_id = $business['id'];
$sqlProducts = "SELECT * FROM products WHERE business_id = ?";
$stmt = $conn->prepare($sqlProducts);
$stmt->bind_param("i", $business_id);
$stmt->execute();
$products = $stmt->get_result();

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setCellValue('A1', 'Business Name')->getStyle('A1')->getFont()->setBold(true);
$sheet->setCellValue('B1', $business ? "ID:{$business['id']} - {$business['name']}" : 'N/A');

$sheet->setCellValue('A2', 'Branch')->getStyle('A2')->getFont()->setBold(true);
$sheet->setCellValue('B2', $branch ? "ID:{$branch['id']} - {$branch['location']}" : 'N/A');

$sheet->setCellValue('A3', 'Product')->getStyle('A3')->getFont()->setBold(true);
$sheet->setCellValue('B3', 'Price')->getStyle('B3')->getFont()->setBold(true);
$sheet->setCellValue('C3', 'Size')->getStyle('C3')->getFont()->setBold(true);

$row = 4;
$productNames = [];
while ($product = $products->fetch_assoc()) {
    $sheet->setCellValue("A$row", "ID:{$product['id']} - {$product['name']}");
    $sheet->setCellValue("B$row", $product['price']);
    $sheet->setCellValue("C$row", $product['size'] ?? 'N/A');
    $productNames[] = "ID:{$product['id']} - {$product['name']}";
    $sheet->setCellValue("E$row", $product['price']);
    $row++;
}

$productListStartRow = 4;
$productListEndRow = $row - 1;
$productDropdownRange = "A$productListStartRow:A$productListEndRow";

$salesHeaderRow = $row + 2;
$sheet->setCellValue("B$salesHeaderRow", 'Sales Report')->getStyle("B$salesHeaderRow")->getFont()->setBold(true);

$salesTableRow = $salesHeaderRow + 1;
$sheet->setCellValue("A$salesTableRow", 'Select Product')->getStyle("A$salesTableRow")->getFont()->setBold(true);
$sheet->setCellValue("B$salesTableRow", 'Amount Sold')->getStyle("B$salesTableRow")->getFont()->setBold(true);
$sheet->setCellValue("C$salesTableRow", 'Total Sales')->getStyle("C$salesTableRow")->getFont()->setBold(true);
$sheet->setCellValue("D$salesTableRow", 'Date')->getStyle("D$salesTableRow")->getFont()->setBold(true);

$dateToday = date('Y-m-d');
for ($i = $salesTableRow + 1; $i <= $salesTableRow + 8; $i++) {
    $sheet->setCellValue("A$i", "");
    $sheet->setCellValue("B$i", 0);
    $sheet->setCellValue("C$i", "=IF(A$i<>\"\",VLOOKUP(A$i,A$productListStartRow:E$productListEndRow,5,FALSE)*B$i,\"\")");
    $sheet->setCellValue("D$i", $dateToday);

    $validation = $sheet->getCell("A$i")->getDataValidation();
    $validation->setType(DataValidation::TYPE_LIST);
    $validation->setErrorStyle(DataValidation::STYLE_STOP);
    $validation->setAllowBlank(false);
    $validation->setShowDropDown(true);
    $validation->setFormula1("=$productDropdownRange");
}

$sheet->getColumnDimension('A')->setWidth(30);
$sheet->getColumnDimension('B')->setWidth(15);
$sheet->getColumnDimension('C')->setWidth(15);
$sheet->getColumnDimension('D')->setWidth(15);
$sheet->getColumnDimension('E')->setVisible(false);

$filename = "Sales_Report_{$dateToday}.xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>