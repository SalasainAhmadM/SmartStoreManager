<?php
require 'vendor/autoload.php';
session_start();
require_once './conn/conn.php';
require_once './conn/auth.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

validateSession('manager');

$manager_id = $_SESSION['user_id'];

if (isset($_FILES['file']['tmp_name'])) {
    $filePath = $_FILES['file']['tmp_name'];

    try {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        // Extract business details
        $businessInfo = [
            'name' => $sheet->getCell('B1')->getValue(),
            'branch' => $sheet->getCell('B2')->getValue(),
        ];

        // Extract product details (starting from Row 4)
        $products = [];
        $row = 4;
        while (true) {
            $productName = $sheet->getCell("A$row")->getValue();
            $price = $sheet->getCell("B$row")->getValue();
            $size = $sheet->getCell("C$row")->getValue();

            if (empty($productName)) {
                break; // Stop when no more products
            }

            $products[$productName] = [
                'price' => $price,
                'size' => $size,
            ];
            $row++;
        }

        // Find the row with the "Select Product" header
        $selectProductHeaderRow = null;
        $amountSoldHeaderRow = null;
        $highestRow = $sheet->getHighestRow();
        for ($i = 1; $i <= $highestRow; $i++) {
            if ($sheet->getCell("A$i")->getValue() == 'Select Product') {
                $selectProductHeaderRow = $i;
            }
            if ($sheet->getCell("B$i")->getValue() == 'Amount Sold') {
                $amountSoldHeaderRow = $i;
            }
            if ($selectProductHeaderRow !== null && $amountSoldHeaderRow !== null) {
                break;
            }
        }

        // Extract sales data starting from the row after the headers
        $salesData = [];
        if ($selectProductHeaderRow !== null && $amountSoldHeaderRow !== null) {
            $startRow = max($selectProductHeaderRow, $amountSoldHeaderRow) + 1;
            for ($row = $startRow; $row <= $highestRow; $row++) {
                $product = $sheet->getCell("A$row")->getValue();
                $amountSold = $sheet->getCell("B$row")->getValue();
                $date = $sheet->getCell("D$row")->getValue();

                if (empty($product)) {
                    break; // Stop when no more sales data
                }

                // Calculate total sales by multiplying amount sold with product price
                if (isset($products[$product])) {
                    $totalSales = $amountSold * $products[$product]['price'];
                } else {
                    $totalSales = 0; // If product not found, set total sales to 0
                }

                $salesData[] = [
                    'product' => $product,
                    'amount_sold' => $amountSold,
                    'total_sales' => $totalSales,
                    'date' => $date,
                ];
            }
        }

        ?>

        <!DOCTYPE html>
        <html lang="en">

        <head>
            <title>Sales Report</title>
            <link rel="icon" href="./assets/logo.png">
            <link href="./css/excel.css" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        </head>

        <body class="container mt-5 mb-5">
            <!-- Scroll Button -->
            <button id="scrollButton" class="animated">↓</button>
            <h2>Business Information</h2>
            <table class="table table-bordered mb-5">
                <thead class="table-dark">
                    <tr>
                        <th>Business Name</th>
                        <th>Branch</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo htmlspecialchars($businessInfo['name']); ?></td>
                        <td><?php echo htmlspecialchars($businessInfo['branch']); ?></td>
                    </tr>
                </tbody>
            </table>

            <h2>Products</h2>
            <table class="table table-bordered mb-5">
                <thead class="table-dark">
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Size</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $productName => $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($productName); ?></td>
                            <td>₱<?php echo number_format($product['price'], 2); ?></td>
                            <td><?php echo htmlspecialchars($product['size']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2>Sales Report</h2>
            <?php if (!empty($salesData)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Amount Sold</th>
                            <th>Total Sales</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($salesData as $sale): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($sale['product']); ?></td>
                                <td><?php echo $sale['amount_sold']; ?></td>
                                <td>₱<?php echo number_format($sale['total_sales'], 2); ?></td>
                                <td><?php echo $sale['date']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-warning">No sales data available.</div>
            <?php endif; ?>

            <form id="importForm" action="import_sales_manager.php" method="POST" class="mt-4">
                <input type="hidden" name="data"
                    value='<?php echo htmlspecialchars(json_encode(["businessInfo" => $businessInfo, "products" => $products, "salesData" => $salesData])); ?>'>

                <button type="button" class="btn btn-primary" id="confirmImport">Confirm Import</button>
                <a href="./manager/index.php" class="btn btn-secondary">Cancel</a>
            </form>
        </body>
        <script>
            document.getElementById("confirmImport").addEventListener("click", function () {
                Swal.fire({
                    title: "Are you sure?",
                    text: "You are about to import this data!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Yes, import it!"
                }).then((result) => {
                    if (result.isConfirmed) {
                        let form = document.getElementById("importForm");
                        form.action = "./endpoints/sales/import_sales_manager.php";
                        form.submit();
                    }
                });
            });

        </script>
        <script src="./js/excel.js"></script>

        </html>

        <?php
    } catch (Exception $e) {
        echo "<script>
            Swal.fire({
                title: 'Error!',
                text: 'Error reading file: " . addslashes($e->getMessage()) . "',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        </script>";
    }
} else {
    echo "<script>
        Swal.fire({
            title: 'No File Uploaded!',
            text: 'Please upload a valid Excel file.',
            icon: 'error',
            confirmButtonText: 'OK'
        });
    </script>";
}
?>