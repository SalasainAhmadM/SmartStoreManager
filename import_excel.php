<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if (isset($_FILES['file']['tmp_name'])) {
    $filePath = $_FILES['file']['tmp_name'];

    // Load the uploaded Excel file
    $spreadsheet = IOFactory::load($filePath);
    $sheet = $spreadsheet->getActiveSheet();

    $data = [];
    foreach ($sheet->getRowIterator(2) as $row) { // Start from row 2 to skip headers
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);

        $rowData = [];
        foreach ($cellIterator as $cell) {
            $rowData[] = $cell->getValue();
        }

        // Skip empty rows
        if (!empty(array_filter($rowData))) {
            $data[] = [
                'name' => $rowData[0] ?? '',
                'birthday' => $rowData[1] ?? '',
                'age' => $rowData[2] ?? '',
            ];
        }
    }

    // Redirect back to HTML page with data
    header('Location: excel.php?data=' . urlencode(json_encode($data)));
    exit;
} else {
    echo "No file uploaded!";
}
?>