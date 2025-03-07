<?php
require 'vendor/autoload.php';
session_start();
require_once './conn/conn.php';
require_once './conn/auth.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

validateSession('owner');

$owner_id = $_SESSION['user_id'];

if (isset($_FILES['file']['tmp_name'])) {
    $filePath = $_FILES['file']['tmp_name'];

    try {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        // Extract business details
        $businessInfo = [
            'name' => $sheet->getCell('B1')->getValue(),
        ];

        // Extract branches
        $branches = [];
        $row = 3; // Assuming branches start from row 3
        while (true) {
            $branchId = $sheet->getCell("A$row")->getValue();
            $branchName = $sheet->getCell("B$row")->getValue();

            if (empty($branchId) && empty($branchName))
                break; // Stop when no more branches

            $branches[] = [
                'id' => $branchId,
                'name' => $branchName
            ];
            $row++;
        }

        // Find the starting row for sales data
        while (true) {
            $cellValue = $sheet->getCell("A$row")->getValue();
            if ($cellValue === 'Products') {
                $row++; // Move to the next row where the data starts
                break;
            }
            $row++;
        }

        // Extract sales data
        $salesData = [];
        while (true) {
            $product = $sheet->getCell("A$row")->getValue();
            $price = $sheet->getCell("B$row")->getValue();
            $amountSold = $sheet->getCell("C$row")->getValue();
            $totalSales = $sheet->getCell("D$row")->getValue();
            $date = $sheet->getCell("E$row")->getValue();
            $businessBranch = $sheet->getCell("F$row")->getValue();

            if (empty($product) && empty($price) && empty($amountSold) && empty($totalSales) && empty($date) && empty($businessBranch))
                break; // Stop when no more data

            // Skip rows where Amount Sold & Total Sales are zero
            if ($amountSold > 0 || $totalSales > 0) {
                $salesData[] = [
                    'product' => $product,
                    'price' => $price,
                    'amount_sold' => $amountSold,
                    'total_sales' => $totalSales,
                    'date' => $date,
                    'business_branch' => $businessBranch
                ];
            }
            $row++;
        }
        ?>

        <!DOCTYPE html>
        <html lang="en">

        <head>
            <title>Whole Sales Report</title>
            <link rel="icon" href="./assets/logo.png">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        </head>

        <body class="container mt-5">
            <h2>Business Information</h2>
            <table class="table table-bordered mb-5">
                <thead class="table-dark">
                    <tr>
                        <th>Business Name</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo htmlspecialchars($businessInfo['name']); ?></td>
                    </tr>
                </tbody>
            </table>

            <h2>Branches</h2>
            <table class="table table-bordered mb-5">
                <thead class="table-dark">
                    <tr>
                        <th>Branch ID/Branch Name</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($branches as $branch): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($branch['name']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h2>Sales Report</h2>
            <?php
            $totalAmountSold = array_sum(array_column($salesData, 'amount_sold'));

            if (!empty($salesData) && $totalAmountSold > 0): ?>
                <table class="table table-bordered mb-5">
                    <thead class="table-dark">
                        <tr>
                            <th>Products</th>
                            <th>Price</th>
                            <th>Amount Sold</th>
                            <th>Total Sales</th>
                            <th>Date</th>
                            <th>Business/Branch</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($salesData as $sale) {
                            $product = $sale['product'];
                            $price = $sale['price'];
                            $amountSold = $sale['amount_sold'];
                            $totalSales = $price * $amountSold; // Calculate total sales
                            $date = $sale['date'];
                            $businessBranch = $sale['business_branch'];

                            // Skip rows where both Amount Sold and Total Sales are 0
                            if ($amountSold == 0 && $totalSales == 0) {
                                continue;
                            }
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product); ?></td>
                                <td><?php echo '₱ ' . number_format($price, 2); ?></td>
                                <td><?php echo number_format($amountSold); ?></td>
                                <td><?php echo '₱ ' . number_format($totalSales, 2); ?></td>
                                <td><?php echo htmlspecialchars($date); ?></td>
                                <td><?php echo htmlspecialchars($businessBranch); ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>

                </table>
            <?php else: ?>
                <div class="alert alert-warning">No sales data available.</div>
            <?php endif; ?>

            <form id="importForm" action="import_whole_sales.php" method="POST" class="mt-4">
                <input type="hidden" name="data"
                    value='<?php echo htmlspecialchars(json_encode(["businessInfo" => $businessInfo, "branches" => $branches, "salesData" => $salesData])); ?>'>

                <button type="button" class="btn btn-primary" id="confirmImport">Confirm Import</button>
                <a href="./owner/tracksales.php" class="btn btn-secondary">Cancel</a>
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
                        form.action = "./endpoints/sales/import_whole_sales.php";
                        form.submit();
                    }
                });
            });
        </script>

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