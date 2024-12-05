<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if (isset($_FILES['file']['tmp_name'])) {
    $filePath = $_FILES['file']['tmp_name'];

    $spreadsheet = IOFactory::load($filePath);
    $sheet = $spreadsheet->getActiveSheet();

    // Extract the year and month from the title (A1)
    $yearMonth = $sheet->getCell('A1')->getValue(); // Assuming A1 contains "Sales Report for November 2024"

    $yearMonth = str_replace('Sales Report for ', '', $yearMonth);

    $data = [];
    foreach ($sheet->getRowIterator(3) as $row) { // Start from row 3 to skip the headers (A2, B2, C2, D2)
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);

        $rowData = [];
        foreach ($cellIterator as $cell) {
            $rowData[] = $cell->getValue();
        }

        // Skip empty rows
        if (!empty(array_filter($rowData))) {
            $data[] = [
                'business' => $rowData[0] ?? '',
                'branches' => $rowData[1] ?? '',
                'sales' => $rowData[2] ?? '',
                'expenses' => $rowData[3] ?? '',
            ];
        }
    }

    header('Location: ./owner/index.php?data=' . urlencode(json_encode($data)) . '&yearMonth=' . urlencode($yearMonth));
    exit;
} else {
    echo "No file uploaded!";
}
?>
