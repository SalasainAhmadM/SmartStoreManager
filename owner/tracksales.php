<?php
session_start();
require_once '../conn/auth.php';
require_once '../conn/conn.php';
validateSession('owner');

$owner_id = $_SESSION['user_id'];

// $conn->query("SET time_zone = '+08:00'");
// Set the timezone to Philippine Time (Asia/Manila)
date_default_timezone_set('Asia/Manila');
$today = date("Y-m-d");

// Fetch businesses owned by the logged-in user
$query = "SELECT id, name FROM business WHERE owner_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$business_result = $stmt->get_result();

$businesses = [];
while ($row = $business_result->fetch_assoc()) {
    $businesses[$row['id']] = $row['name'];
}
$stmt->close();

// Fetch products for each business
$products_by_business = [];
if (!empty($businesses)) {
    $business_ids = implode(",", array_keys($businesses));
    $product_query = "SELECT id, name, price, business_id FROM products WHERE business_id IN ($business_ids)";
    $product_result = $conn->query($product_query);

    while ($product = $product_result->fetch_assoc()) {
        $products_by_business[$product['business_id']][] = $product;
    }
}
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
                    <h1><b><i class="fas fa-chart-line me-2"></i> Track Sales</b></h1>

                    <!-- Search Bar for Sales -->
                    <div class="mt-5 position-relative">
                        <form class="d-flex" role="search">
                            <input id="saleSearchBar" class="form-control me-2 w-50" type="search"
                                placeholder="Search sale.." aria-label="Search" onkeyup="searchSales()">
                        </form>
                        <!-- Add Sale Button -->
                        <button class="btn btn-success position-absolute top-0 end-0 mt-2 me-2" type="button"
                            id="addSaleButton">
                            <i class="fas fa-plus me-2"></i> Add Sale
                        </button>
                    </div>

                    <!-- Business Selection Dropdown -->
                    <div class="mt-4">
                        <div class="form-group">
                            <label for="businessSelect"><i class="fas fa-briefcase me-2"></i> Select Business</label>
                            <select id="businessSelect" class="form-control">
                                <option value=""><strong>Select Business</strong></option>
                                <?php foreach ($businesses as $id => $name): ?>
                                    <option value="<?php echo $id; ?>"><?php echo $name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <script>
                        const businesses = <?php echo json_encode($businesses); ?>;
                        const productsByBusiness = <?php echo json_encode($products_by_business); ?>;
                    </script>


                    <!-- Sales Tables (Initially hidden) -->
                    <div id="businessA_sales" style="display:none;">
                        <h2 class="mt-5 mb-3"><b>Today’s Sales for Business A (<?php echo $today; ?>)</b></h2>
                        <div class="scrollable-table">
                            <table id="salesTableA" class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Product</th>
                                        <th>Amount Sold</th>
                                        <th>Total Sales</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                                <tfoot>
                                    <tr>
                                        <th><strong>Total</strong></th>
                                        <th>0</th>
                                        <th>₱0</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <div id="businessB_sales" style="display:none;">
                        <h2 class="mt-5 mb-3"><b>Today’s Sales for Business B (<?php echo $today; ?>)</b></h2>
                        <div class="scrollable-table">
                            <table id="salesTableB" class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Product</th>
                                        <th>Amount Sold</th>
                                        <th>Total Sales</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                                <tfoot>
                                    <tr>
                                        <th><strong>Total</strong></th>
                                        <th>0</th>
                                        <th>₱0</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>



                    <h2 class="mt-5 mb-3"><b>Sales Log</b></h2>

                    <table id="salesLogTable" class="table table-striped table-hover">

                        <div class="mt-4 mb-4 position-relative">
                            <form class="d-flex" role="search">
                                <input id="saleSearchBar" class="form-control me-2 w-50" type="search"
                                    placeholder="Search branch.." aria-label="Search" style="visibility:hidden;">
                            </form>
                            <!-- Date Filter Button for Sales Log -->
                            <div class="position-absolute top-0 end-0 mt-2 me-2">
                                <button class="btn btn-success" id="filterDateButton">
                                    <i class="fas fa-calendar-alt me-2"></i> Filter by Date
                                </button>
                                <button class="btn btn-danger" id="resetButton" onclick="resetFilter()">
                                    <i class="fas fa-times-circle me-2"></i> Reset Filter
                                </button>
                            </div>

                        </div>


                        <thead class="table-dark">
                            <tr>
                                <th>Product</th>
                                <th>Amount Sold</th>
                                <th>Total Sales</th>
                                <th>Business</th>
                                <th>Date</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td>Product 1</td>
                                <td>₱100</td>
                                <td>₱10000</td>
                                <td>Business A</td>
                                <td>11/30/2024</td>
                            </tr>
                            <tr>
                                <td>Product 2</td>
                                <td>₱100</td>
                                <td>₱10000</td>
                                <td>Business A</td>
                                <td>11/05/2024</td>
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

    <script src="../js/business_tracksales_filter.js"></script>
    <script src="../js/business_tracksales_add_sale.js"></script>

</body>

</html>