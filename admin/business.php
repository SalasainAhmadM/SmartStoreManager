<?php
session_start();
require_once '../conn/auth.php';
require_once '../conn/conn.php';
validateSession('admin');
date_default_timezone_set('Asia/Manila');
$admin_id = $_SESSION['user_id'];


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Business</title>
    <link rel="icon" href="../assets/logo.png">
    <?php include '../components/head_cdn.php'; ?>
</head>

<body class="d-flex">

    <div id="particles-js"></div>

    <?php include '../components/admin_sidebar.php'; ?>

    <div class="container-fluid page-body">
        <div class="row">
            <div class="col-md-12 dashboard-body">
                <div class="dashboard-content">
                    <h1><b>Businesses</b></h1>
                    <h4 class="mt-5"><b><i class="fas fa-tachometer-alt me-2"></i> Manage</b></h4>
                    <div class="card-one">

                        <div id="salesReportPanel">
                            <h4 class="mt-4" id="reportTitle"></h4>

                            <div class="mt-4 position-relative">
                                <form class="d-flex" role="search">
                                    <input class="form-control me-2 w-50" type="search" placeholder="Search product.."
                                        aria-label="Search" id="productSearchInput">
                                </form>

                                <div class="position-absolute top-0 end-0 mt-2 me-2">
                                    <button class="btn btn-success" type="button">
                                        <i class="fas fa-plus me-2"></i> Filter Date
                                    </button>
                                    <button class="btn btn-danger" id="resetButton">
                                        <i class="fas fa-times-circle me-2"></i> Reset Filter
                                    </button>
                                </div>


                            </div>

                            <div class="scrollable-table">
                                <table class="table mt-3" id="salesReportTable">
                                    <thead class="table-dark position-sticky top-0">
                                        <tr>
                                            <th>Product<button class="btn text-white"><i
                                                        class="fas fa-sort"></i></button>
                                            </th>
                                            <th>Amount Sold <button class="btn text-white"><i
                                                        class="fas fa-sort"></i></button></th>
                                            <th>Total Sales (PHP) <button class="btn text-white"><i
                                                        class="fas fa-sort"></i></button></th>
                                            <th>Date <button class="btn text-white"><i class="fas fa-sort"></i></button>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody id="salesReportBody">

                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="2"><strong>Total Sales</strong></td>
                                            <td id="totalSalesCell">
                                                <!-- Total Sales will be displayed here -->
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>

                            <button class="btn btn-primary mt-2 mb-5" id="printReportBtn" onclick="printSalesReport()">
                                <i class="fas fa-print me-2"></i> Print Sales Report
                            </button>

                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>



</html>