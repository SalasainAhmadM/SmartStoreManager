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

        // Extract Business, Branches, and Expense Types
        $businesses = [];
        $branches = [];
        $expenseTypes = [];

        for ($row = 2; $row <= 10; $row++) {
            $businesses[] = $sheet->getCell("A$row")->getValue();
            $branches[] = $sheet->getCell("B$row")->getValue();
            $expenseTypes[] = $sheet->getCell("C$row")->getValue();
        }

        // Extract expense details
        $expenseData = [];
        $row = 16;
        while (true) {
            $expenseType = $sheet->getCell("A$row")->getValue();
            $amount = $sheet->getCell("B$row")->getValue();
            $category = $sheet->getCell("C$row")->getValue();
            $business = $sheet->getCell("D$row")->getValue();
            $branch = $sheet->getCell("E$row")->getValue();
            $description = $sheet->getCell("F$row")->getValue();
            $date = $sheet->getCell("G$row")->getValue();

            if (empty($expenseType)) {
                break;
            }

            $expenseData[] = [
                'expense_type' => $expenseType,
                'amount' => $amount,
                'category' => $category,
                'business' => $business,
                'branch' => $branch,
                'description' => $description,
                'date' => $date
            ];
            $row++;
        }
        ?>

        <!DOCTYPE html>
        <html lang="en">

        <head>
            <title>Expenses Report</title>
            <link rel="icon" href="./assets/logo.png">
            <link href="./css/excel.css" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        </head>

        <body class="container mt-5 mb-5">
            <!-- Scroll Button -->
            <button id="scrollButton" class="animated">↓</button>
            <div class="mb-4">
                <h2>Business, Branches & Expense Types</h2>
                <table class="table table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Business</th>
                            <th>Branch/Branches</th>
                            <th>Expense Types</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($i = 0; $i < count($businesses); $i++): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($businesses[$i]); ?></td>
                                <td><?php echo htmlspecialchars($branches[$i]); ?></td>
                                <td><?php echo htmlspecialchars($expenseTypes[$i]); ?></td>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($expenseData)): ?>
                <h2>Expenses Report</h2>
                <table class="table table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Expense Type</th>
                            <th>Amount</th>
                            <th>Category</th>
                            <th>Business</th>
                            <th>Branch</th>
                            <th>Description</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expenseData as $expense): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($expense['expense_type']); ?></td>
                                <td><?php echo htmlspecialchars($expense['amount']); ?></td>
                                <td><?php echo htmlspecialchars($expense['category']); ?></td>
                                <td><?php echo htmlspecialchars($expense['business']); ?></td>
                                <td><?php echo htmlspecialchars($expense['branch']); ?></td>
                                <td><?php echo htmlspecialchars($expense['description']); ?></td>
                                <td><?php echo htmlspecialchars($expense['date']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-warning">No expense data available.</div>
            <?php endif; ?>

            <form id="importForm" action="import_expense.php" method="POST" class="mt-4">
                <input type="hidden" name="data" value='<?php echo htmlspecialchars(json_encode($expenseData)); ?>'>
                <button type="button" class="btn btn-primary" id="confirmImport">Confirm Import</button>
                <a href="./owner/manageexpenses.php" class="btn btn-secondary">Cancel</a>
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
                        form.action = "./endpoints/expenses/import_expenses_business.php";
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