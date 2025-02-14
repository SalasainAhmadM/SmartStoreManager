<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if (isset($_FILES['file']['tmp_name'])) {
    $filePath = $_FILES['file']['tmp_name'];

    try {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        // Extract business information (Row 2)
        $businessInfo = [
            'name' => $sheet->getCell('A2')->getValue(),
            'description' => $sheet->getCell('B2')->getValue(),
            'asset_size' => $sheet->getCell('C2')->getValue(),
            'employee_count' => $sheet->getCell('D2')->getValue()
        ];

        // Extract product information (starting from Row 5)
        $products = [];
        $row = 5;
        while (true) {
            $name = $sheet->getCell("A$row")->getValue();
            if (empty($name)) break; // Stop if no more products

            $products[] = [
                'name' => $name,
                'type' => $sheet->getCell("B$row")->getValue(),
                'price' => $sheet->getCell("C$row")->getValue(),
                'description' => $sheet->getCell("D$row")->getValue()
            ];
            $row++;
        }

        // Display the data in tables
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Import Preview</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="container mt-5">
            <h2>Business Information</h2>
            <table class="table table-bordered mb-5">
                <thead class="table-dark">
                    <tr>
                        <th>Business Name</th>
                        <th>Description</th>
                        <th>Asset Size</th>
                        <th>Number of Employees</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo htmlspecialchars($businessInfo['name']); ?></td>
                        <td><?php echo htmlspecialchars($businessInfo['description']); ?></td>
                        <td><?php echo htmlspecialchars($businessInfo['asset_size']); ?></td>
                        <td><?php echo htmlspecialchars($businessInfo['employee_count']); ?></td>
                    </tr>
                </tbody>
            </table>

            <h2>Products</h2>
            <table class="table table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Price</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><?php echo htmlspecialchars($product['type']); ?></td>
                        <td><?php echo htmlspecialchars($product['price']); ?></td>
                        <td><?php echo htmlspecialchars($product['description']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <form action="./owner/managebusiness.php" method="POST" class="mt-4">
                <input type="hidden" name="data" value="<?php echo htmlspecialchars(json_encode(['business' => $businessInfo, 'products' => $products])); ?>">
                <button type="submit" class="btn btn-primary">Confirm Import</button>
                <a href="./owner/managebusiness.php" class="btn btn-secondary">Cancel</a>
            </form>
        </body>
        </html>
        <?php
    } catch (Exception $e) {
        echo "Error reading file: " . $e->getMessage();
    }
} else {
    echo "No file uploaded!";
}
?>
