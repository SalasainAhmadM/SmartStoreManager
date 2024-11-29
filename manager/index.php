<?php
session_start();
require_once '../conn/auth.php';
require_once '../conn/conn.php';
validateSession('manager');

$manager_id = $_SESSION['user_id'];

if (isset($_SESSION['login_success']) && $_SESSION['login_success']) {
    echo "
        <script>
            window.onload = function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Login Successful',
                    text: 'Welcome!',
                    timer: 2000,
                    showConfirmButton: false
                });
            };
        </script>
    ";
    unset($_SESSION['login_success']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard</title>
    <link rel="icon" href="../assets/logo.png">
    <?php include '../components/head_cdn.php'; ?>
</head>

<body class="d-flex">

    <div id="particles-js"></div>

    <?php include '../components/manager_sidebar.php'; ?>

    <div class="container-fluid page-body">
        <div class="row">
            <div class="col-md-12 dashboard-body">
                <div class="dashboard-content">
                    <h1><b>Manager Dashboard</b></h1>
                    <h4 class="mt-5"><b><i class="fas fa-tachometer-alt me-2"></i> Manage Sales</b></h4>
                    <div class="card-one">

                        <h5 class="mt-5"><b>Select Business:</b></h5>
                        <div class="mt-4 mb-4 position-relative">
                            <select class="form-select w-50" id="businessSelect">
                                <option value="">Select Business</option>
                                <option value="A">Business A</option>
                                <option value="B">Business B</option>
                            </select>
                        </div>

                        <div id="salesPanel" class="collapse">
                            <h4 class="mt-4" id="salesTitle"></h4>
                            <button class="btn btn-success mt-2 mb-5" id="addSaleBtn">
                                <i class="fas fa-plus me-2"></i> Add Sale
                            </button>

                            <!-- Search Bar -->
                            <div class="mt-4">
                                <form class="d-flex" role="search">
                                <input class="form-control me-2 w-50" type="search" placeholder="Search product.." aria-label="Search" id="searchInput">
                                </form>
                            </div>

                            <div class="scrollable-table">
                            <table class="table mt-3">
                                <table class="table table-striped table-hover mt-4">
                                    <thead class="table-dark">
                                        <th>Product <button class="btn text-white"><i
                                        class="fas fa-sort"></i></button></th>
                                        <th>Price <button class="btn text-white"><i
                                        class="fas fa-sort"></i></button></th>                                        
                                        <th>Quantity Sold <button class="btn text-white"><i
                                        class="fas fa-sort"></i></button></th>
                                        <th>Revenue <button class="btn text-white"><i
                                        class="fas fa-sort"></i></button></th>
                                        <th>Updated At <button class="btn text-white"><i
                                        class="fas fa-sort"></i></button></th>
                                        </tr>
                                    </thead>
                                    <tbody id="salesTableBody">
                                        <!-- Sales Data will be dynamically populated here -->
                                        <script src="../js/manager_add_sale.js"></script>
                                    </tbody>
                                </table>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/sidebar_manager.js"></script>
    <script src="../js/sort_items.js"></script>

    <script>
        const searchInput = document.getElementById('searchInput');
        const salesTableBody = document.getElementById('salesTableBody');

        searchInput.addEventListener('input', function () {
            const searchValue = searchInput.value.toLowerCase();

            // Get all rows in the sales table
            const rows = salesTableBody.getElementsByTagName('tr');

            // Loop through rows and toggle their visibility based on the search value
            for (let row of rows) {
                const cells = row.getElementsByTagName('td');
                let rowMatches = false;

                // Check each cell in the row
                for (let cell of cells) {
                    if (cell.textContent.toLowerCase().includes(searchValue)) {
                        rowMatches = true;
                        break;
                    }
                }
                
                row.style.display = rowMatches ? '' : 'none';
            }
        });
    </script>

</body>

</html>