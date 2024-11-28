<?php
session_start();
require_once '../conn/auth.php';

validateSession('owner');

$owner_id = $_SESSION['user_id'];

// Get today's date
$today = date("F j, Y");

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

    <div id="particles-js"></div>

    <?php include '../components/owner_sidebar.php'; ?>

    <div class="container-fluid page-body">
        <div class="row">
            <div class="col-md-12 dashboard-body">
                <div class="dashboard-content">
                    <h1><b><i class="fas fa-chart-line me-2"></i> Track Sales</b></h1>

                    <div class="mt-5">
                        <div class="form-group">
                            <select id="businessSelect" class="form-control">
                                <option value=""><strong>Select Business</strong></option>
                                <option value="A">Business A</option>
                                <option value="B">Business B</option>
                            </select>
                        </div>
                    </div>

                    <!-- Search Bar -->
                    <div class="mt-4 mb-4 position-relative">
                        <form class="d-flex" role="search">
                            <input class="form-control me-2 w-50" type="search" placeholder="Search sale.." aria-label="Search">
                        </form>
                        <!-- Add Sale Button -->
                        <button class="btn btn-success position-absolute top-0 end-0 mt-2 me-2" type="button">
                            <i class="fas fa-plus me-2"></i> Add Sale
                        </button>
                    </div>

                    <h1 class="mt-5">
                        <i class="fa-solid fa-dollar-sign" style="margin-right: 10px;"></i>
                        <b>Today Sales for Business A (<?php echo $today; ?>)</b>
                    </h1>

                    <div class="mt-4">
                    <table class="table table-striped table-hover mt-4">
                    <thead class="table-dark">
                                <tr>
                                    <th>Product <button class="btn text-white"><i class="fas fa-sort"></i></button></th>
                                    <th>Amount Sold <button class="btn text-white"><i class="fas fa-sort"></i></button></th>
                                    <th>Total Sales <button class="btn text-white"><i class="fas fa-sort"></i></button></th>
                                    <th>Date <button class="btn text-white"><i class="fas fa-sort"></i></button></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Product 1</td>
                                    <td>10</td>
                                    <td>₱100</td>
                                    <td><?php echo $today; ?></td>
                                </tr>
                                <tr>
                                    <td>Product 2</td>
                                    <td>15</td>
                                    <td>₱150</td>
                                    <td><?php echo $today; ?></td>
                                </tr>
                                <tr>
                                    <td>Product 3</td>
                                    <td>20</td>
                                    <td>₱200</td>
                                    <td><?php echo $today; ?></td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th>Total</th>
                                    <th>45</th>
                                    <th>₱450</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="mt-5">
                        <button class="btn btn-info" type="button" data-bs-toggle="collapse" data-bs-target="#previousSalesTable" aria-expanded="false" aria-controls="previousSalesTable">
                            <i class="fas fa-calendar-day me-2"></i><b>View Sales Log</b> <i class="fas fa-plus me-2"></i>

                        </button>
                    </div>

                    <div class="collapse mt-3" id="previousSalesTable">
                        <h3><b>Sales Log</b></h3>
                        <table class="table table-striped table-hover mt-4">
                        <thead class="table-dark">
                                <tr>
                                    <th>Product <button class="btn text-white"><i class="fas fa-sort"></i></button></th>
                                    <th>Amount Sold <button class="btn text-white"><i class="fas fa-sort"></i></button></th>
                                    <th>Total Sales <button class="btn text-white"><i class="fas fa-sort"></i></button></th>
                                    <th>Date <button class="btn text-white"><i class="fas fa-sort"></i></button></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Product 1</td>
                                    <td>12</td>
                                    <td>₱120</td>
                                    <td>November 24, 2024</td>
                                </tr>
                                <tr>
                                    <td>Product 2</td>
                                    <td>18</td>
                                    <td>₱180</td>
                                    <td>November 24, 2024</td>
                                </tr>
                                <tr>
                                    <td>Product 3</td>
                                    <td>25</td>
                                    <td>₱250</td>
                                    <td>November 24, 2024</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>

        </div>

    </div>

    <script src="../js/sidebar.js"></script>
    <script src="../js/sort_items.js"></script>
    
</body>

</html>
