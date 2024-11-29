<?php
session_start();
require_once '../conn/auth.php';
require_once '../conn/conn.php';
validateSession('manager');

$manager_id = $_SESSION['user_id'];

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Reports</title>
    <link rel="icon" href="../assets/logo.png">
    <?php include '../components/head_cdn.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0-alpha1/css/bootstrap.min.css">
    <script src="https://kit.fontawesome.com/a076d05399.js"></script> <!-- FontAwesome CDN -->
</head>

<body class="d-flex">

    <div id="particles-js"></div>

    <?php include '../components/manager_sidebar.php'; ?>

    <div class="container-fluid page-body">
        <div class="row">
            <div class="col-md-12 dashboard-body">
                <div class="dashboard-content">
                    <h1><b>Manager Dashboard</b></h1>
                    <h4 class="mt-5"><b><i class="fas fa-tachometer-alt me-2"></i> View Reports</b></h4>
                    <div class="card-one">

                        <h5 class="mt-5"><b>Select Business:</b></h5>
                        <div class="mt-4 mb-4 position-relative">
                            <select class="form-select w-50" id="businessSelect">
                                <option value="">Select Business</option>
                                <option value="A">Business A</option>
                                <option value="B">Business B</option>
                            </select>
                        </div>

                        <div id="salesReportPanel" class="collapse">
                            <h4 class="mt-4" id="reportTitle"></h4>
                            <button class="btn btn-primary mt-2 mb-5" id="printReportBtn">
                                <i class="fas fa-print me-2"></i> Print Sales Report
                            </button>

                            <div class="mt-4">
                                <form class="d-flex" role="search">
                                    <input class="form-control me-2 w-50" type="search" placeholder="Search product.."
                                        aria-label="Search">
                                </form>
                            </div>


                            <table class="table mt-3">
                            <table class="table table-striped table-hover mt-4">
                                <thead class="table-dark">
                                        <th>Date <button class="btn text-white"><i
                                        class="fas fa-sort"></i></button></th>
                                        <th>Product Sold <button class="btn text-white"><i
                                        class="fas fa-sort"></i></button></th>
                                        <th>Total Sales (PHP) <button class="btn text-white"><i
                                        class="fas fa-sort"></i></button></th>
                                        </tr>
                                    </thead>
                                    <tbody id="salesReportBody">
                                        <!-- Sales Data will be dynamically populated here -->
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="2"><strong>Total Sales</strong></td>
                                            <td id="totalSalesCell"><!-- Total Sales will be displayed here --></td>
                                        </tr>
                                    </tfoot>
                                </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/sidebar_manager.js"></script>
    <script src="../js/sort_items.js"></script>
    
    <script src="../js/manager_view_reports.js"></script>

</body>

</html>