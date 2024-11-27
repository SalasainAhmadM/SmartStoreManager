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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0-alpha1/css/bootstrap.min.css">
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                                    <input class="form-control me-2 w-50" type="search" placeholder="Search product.."
                                        aria-label="Search">
                                </form>
                            </div>

                            <table class="table mt-3">
                                <table class="table table-striped table-hover mt-4">
                                    <thead class="table-dark">
                                        <th>Product</th>
                                        <th>Quantity Sold</th>
                                        <th>Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody id="salesTableBody">
                                        <!-- Sales Data will be dynamically populated here -->
                                    </tbody>
                                </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/sidebar_manager.js"></script>
    <script>
        document.getElementById('businessSelect').addEventListener('change', function () {
            var selectedBusiness = this.value;
            var salesPanel = document.getElementById('salesPanel');
            var salesTitle = document.getElementById('salesTitle');
            var salesTableBody = document.getElementById('salesTableBody');

            salesTableBody.innerHTML = '';

            if (selectedBusiness === 'A') {
                salesTitle.textContent = 'Sales for Business A';
                // Example Sales Data for Business A
                salesTableBody.innerHTML = `
                    <tr><td>Product 1</td><td>100</td><td>$1000</td></tr>
                    <tr><td>Product 2</td><td>50</td><td>$500</td></tr>
                `;
            } else if (selectedBusiness === 'B') {
                salesTitle.textContent = 'Sales for Business B';
                // Example Sales Data for Business B
                salesTableBody.innerHTML = `
                    <tr><td>Product 3</td><td>200</td><td>$2000</td></tr>
                    <tr><td>Product 4</td><td>75</td><td>$750</td></tr>
                `;
            }

            if (selectedBusiness) {
                salesPanel.classList.add('show');
            } else {
                salesPanel.classList.remove('show');
            }
        });

        document.getElementById('addSaleBtn').addEventListener('click', function () {
            // SweetAlert for adding a sale
            Swal.fire({
                title: 'Add New Sale',
                html: `
                    <div class="mb-3">
                        <label for="productSelect" class="form-label">Product</label>
                        <select id="productSelect" class="form-select">
                            <option value="Product 1">Product 1</option>
                            <option value="Product 2">Product 2</option>
                            <option value="Product 3">Product 3</option>
                            <option value="Product 4">Product 4</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="quantitySold" class="form-label">Quantity Sold</label>
                        <input type="number" id="quantitySold" class="form-control" placeholder="Enter quantity">
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Add Sale',
                cancelButtonText: 'Close',
                preConfirm: () => {
                    const product = document.getElementById('productSelect').value;
                    const quantity = document.getElementById('quantitySold').value;
                    if (!product || !quantity) {
                        Swal.showValidationMessage('Please fill in all fields');
                        return false;
                    }
                    return {
                        product,
                        quantity
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const {
                        product,
                        quantity
                    } = result.value;
                    // Here you can add logic to save the sale and update the table
                    Swal.fire('Sale Added!', `Product: ${product}, Quantity: ${quantity}`, 'success');
                    // You can add the sale data to the table dynamically here
                }
            });
        });
    </script>
</body>

</html>