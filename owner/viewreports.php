<?php
session_start();
require_once '../conn/conn.php';
require_once '../conn/auth.php';

validateSession('owner');

$owner_id = $_SESSION['user_id'];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard</title>
    <link rel="icon" href="../assets/logo.png">
    <?php include '../components/head_cdn.php'; ?>
</head>

<body class="d-flex">

    <div id="particles-js"></div>

    <?php include '../components/owner_sidebar.php'; ?>

    <div class="container-fluid page-body">
        <div class="row">
            <div class="col-md-12 dashboard-body">
                <div class="dashboard-content">
                    <h1 class="mb-4">
                        <b><i class="fas fa-chart-bar me-2"></i> View Reports</b>
                    </h1>

                    <h5 class="mt-5"><b>Business Overview:</b></h5>

                    <div class="table-container scrollable-table-two">
                        <table class="table table-striped table-hover mt-4">
                            <thead class="table-dark position-sticky top-0">
                                <tr>
                                    <th>Business Name <button class="btn text-white"><i
                                                class="fas fa-sort"></i></button></th>
                                    <th>Total Sales (₱) <button class="btn text-white"><i
                                                class="fas fa-sort"></i></button></th>
                                    <th>Total Expenses (₱) <button class="btn text-white"><i
                                                class="fas fa-sort"></i></button></th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Business A</td>
                                    <td>₱11,000</td>
                                    <td>₱6,000</td>
                                    <td><button class="swal2-print-btn view-branches" onclick="showBranchDetails('Business A', [
                                        {branch: 'Branch A1', sales: 8000, expenses: 4000},
                                        {branch: 'Branch A2', sales: 3000, expenses: 2000}
                                    ])">View Branches</button></td>
                                </tr>
                                <tr>
                                    <td>Business B</td>
                                    <td>₱4,000</td>
                                    <td>₱13,000</td>
                                    <td><button class="swal2-print-btn view-branches" onclick="showBranchDetails('Business B', [
                                        {branch: 'Branch B1', sales: 3000, expenses: 8000},
                                        {branch: 'Branch B2', sales: 1000, expenses: 5000}
                                    ])">View Branches</button></td>
                                </tr>
                                <tr>
                                    <td>Business C</td>
                                    <td>₱9,000</td>
                                    <td>₱5,000</td>
                                    <td><button class="swal2-print-btn view-branches" onclick="showBranchDetails('Business C', [
                                        {branch: 'Branch C1', sales: 5000, expenses: 3000},
                                        {branch: 'Branch C2', sales: 4000, expenses: 2000}
                                    ])">View Branches</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/owner_view_reports.js"></script>
    <script src="../js/sidebar.js"></script>
    <script src="../js/sort_items.js"></script>

</body>

</html>