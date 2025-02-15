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
            'branch' => $sheet->getCell('B2')->getValue(),
            'product' => $sheet->getCell('B3')->getValue(),
            'price' => $sheet->getCell('B4')->getValue(),
        ];

        // Extract sales data (starting from Row 8)
        $salesData = [];
        $row = 8;
        while (true) {
            $amountSold = $sheet->getCell("A$row")->getValue();
            $totalSales = $sheet->getCell("B$row")->getValue();
            $date = $sheet->getCell("C$row")->getValue();

            if (empty($date))
                break; // Stop when no more data

            // Only include rows where Amount Sold & Total Sales are NOT zero
            if ($amountSold > 0 || $totalSales > 0) {
                $salesData[] = [
                    'amount_sold' => $amountSold,
                    'total_sales' => $totalSales,
                    'date' => $date
                ];
            }
            $row++;
        }
        ?>

        <!DOCTYPE html>
        <html lang="en">

        <head>
            <title>Sales Report</title>
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
                        <th>Branch</th>
                        <th>Product</th>
                        <th>Price</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo htmlspecialchars($businessInfo['name']); ?></td>
                        <td><?php echo htmlspecialchars($businessInfo['branch']); ?></td>
                        <td><?php echo htmlspecialchars($businessInfo['product']); ?></td>
                        <td><?php echo htmlspecialchars($businessInfo['price']); ?></td>
                    </tr>
                </tbody>
            </table>

            <h2>Sales Report</h2>
            <?php
            $totalAmountSold = array_sum(array_column($salesData, 'amount_sold'));

            if (!empty($salesData) && $totalAmountSold > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Amount Sold</th>
                            <th>Total Sales</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $price = $businessInfo['price'];
                        foreach ($salesData as $sale) {
                            $amountSold = $sale['amount_sold'];
                            $totalSales = $amountSold * $price;
                            $date = $sale['date'];

                            // Skip rows where both Amount Sold and Total Sales are 0
                            if ($amountSold == 0 && $totalSales == 0) {
                                continue;
                            }
                            ?>
                            <tr>
                                <td><?php echo $amountSold; ?></td>
                                <td><?php echo number_format($totalSales, 2); ?></td>
                                <td><?php echo $date; ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-warning">No sales data available.</div>
            <?php endif; ?>



            <form id="importForm" action="import_sales.php" method="POST" class="mt-4">
                <input type="hidden" name="data"
                    value='<?php echo htmlspecialchars(json_encode(["businessInfo" => $businessInfo, "salesData" => $salesData])); ?>'>

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
                        form.action = "./endpoints/sales/import_sales.php";
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