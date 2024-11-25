<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard</title>
    <link rel="icon" href="../assets/logo.png">
    <?php include '../components/head_cdn.php'; ?>
</head>

<?php
session_start();
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

<body class="d-flex">

    <?php include '../components/owner_sidebar.php'; ?>

    <div class="container-fluid page-body">
        <div class="row">
            <div class="col-md-12 dashboard-body">
                <div class="dashboard-content">
                    <h1><b><i class="fas fa-tachometer-alt me-2"></i> Dashboard Overview</b></h1>

                    <div class="container-fluid">
                        <div class="row">

                            <div class="col-md-5">

                                <h5 class="mt-5"><b>Select Business:</b></h5>
                                <div class="scroll-container" style="height: 450px; overflow-y: auto;">
                                    <button class="col-md-12 card">
                                        <h5>Business A</h5>
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th>Branch</th>
                                                    <th>Sales (₱)</th>
                                                    <th>Expenses (₱)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>Branch A1</td>
                                                    <td>8000</td>
                                                    <td>4000</td>
                                                </tr>
                                                <tr>
                                                    <td>Branch A2</td>
                                                    <td>3000</td>
                                                    <td>2000</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        </p>
                                    </button>
                                    <button class="col-md-12 card">
                                        <h5>Business B</h5>
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th>Branch</th>
                                                    <th>Sales (₱)</th>
                                                    <th>Expenses (₱)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>Branch A1</td>
                                                    <td>3000</td>
                                                    <td>8000</td>
                                                </tr>
                                                <tr>
                                                    <td>Branch A2</td>
                                                    <td>1000</td>
                                                    <td>5000</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </button>

                                </div>

                            </div>

                            <div class="col-md-7">

                                <h5 class="mt-5"><b>Sales Overview:</b></h5>
                                <canvas id="financialChart"></canvas>

                            </div>

                            <div class="col-md-12 mt-5">
                                <h1><b><i class="fa-solid fa-lightbulb"></i> Insights</b></h1>
                                <div class="col-md-12 dashboard-content">
                                    <div>
                                        <h5>Predicted Growth:</h5>
                                        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi tincidunt tellus quis ligula semper, vitae bibendum felis lacinia. Donec eleifend tellus ac massa malesuada, a pellentesque dolor scelerisque. Sed feugiat felis vel odio condimentum aliquet. Nulla sit amet urna sed est elementum dapibus non ac mauris. Aenean nec est diam. Maecenas a nisi ut nibh luctus porttitor. Vestibulum pretium auctor condimentum.</p>
                                    </div>
                                    <div>
                                        <h5>Actionable Advice:</h5>
                                        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi tincidunt tellus quis ligula semper, vitae bibendum felis lacinia. Donec eleifend tellus ac massa malesuada, a pellentesque dolor scelerisque. Sed feugiat felis vel odio condimentum aliquet. Nulla sit amet urna sed est elementum dapibus non ac mauris. Aenean nec est diam. Maecenas a nisi ut nibh luctus porttitor. Vestibulum pretium auctor condimentum.</p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12 mt-5">
                                <h1 class="section-title"><b><i class="fas fa-boxes icon"></i> Popular Products/Services</b></h1>
                                <div class="col-md-12 dashboard-content">
                                <table>
                                    <thead>
                                    <tr>
                                        <th>Product/Service</th>
                                        <th>Category</th>
                                        <th>Popularity</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                        <td><i class="fas fa-laptop icon"></i> Laptop Repair</td>
                                        <td>Services</td>
                                        <td><i class="fas fa-fire icon"></i> High</td>
                                    </tr>
                                    <tr>
                                        <td><i class="fas fa-tshirt icon"></i> Custom T-Shirts</td>
                                        <td>Products</td>
                                        <td><i class="fas fa-chart-line icon"></i> Moderate</td>
                                    </tr>
                                    <tr>
                                        <td><i class="fas fa-coffee icon"></i> Coffee Beans</td>
                                        <td>Products</td>
                                        <td><i class="fas fa-arrow-up icon"></i> Trending</td>
                                    </tr>
                                    </tbody>
                                </table>
                                </div>
                            </div>

                            <div class="col-md-12 mt-5">
                                <h1 class="section-title"><b><i class="fas fa-history icon"></i> Recent Activities</b></h1>
                                <div class="col-md-12 dashboard-content">
                                <table>
                                    <thead>
                                    <tr>
                                        <th>Activity</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                        <td><i class="fas fa-user-plus icon"></i> New User Registered</td>
                                        <td>2024-11-20</td>
                                        <td><i class="fas fa-check-circle icon"></i> Completed</td>
                                    </tr>
                                    <tr>
                                        <td><i class="fas fa-file-alt icon"></i> Report Generated</td>
                                        <td>2024-11-21</td>
                                        <td><i class="fas fa-spinner icon"></i> In Progress</td>
                                    </tr>
                                    <tr>
                                        <td><i class="fas fa-shopping-cart icon"></i> Product Ordered</td>
                                        <td>2024-11-22</td>
                                        <td><i class="fas fa-times-circle icon"></i> Failed</td>
                                    </tr>
                                    </tbody>
                                </table>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>

        </div>


    </div>

    <script src="../js/chart.js"></script>
    <script src="../js/sidebar.js"></script>

</body>

</html>