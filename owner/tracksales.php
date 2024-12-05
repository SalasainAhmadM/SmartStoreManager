<?php
session_start();
require_once '../conn/auth.php';
require_once '../conn/conn.php';
validateSession('owner');

$owner_id = $_SESSION['user_id'];

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

// Fetch products and today's sales for each business
$products_by_business = [];
$sales_by_business = [];

if (!empty($businesses)) {
    $query = "
        SELECT p.id AS product_id, p.name AS product_name, p.price, p.business_id,
               s.quantity, s.total_sales, s.date
        FROM products p
        LEFT JOIN sales s ON p.id = s.product_id AND s.date = ? 
        WHERE p.business_id IN (" . implode(",", array_keys($businesses)) . ")";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $today); // Bind the current date
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $products_by_business[$row['business_id']][] = [
            'id' => $row['product_id'],
            'name' => $row['product_name'],
            'price' => $row['price']
        ];
        if (!empty($row['quantity'])) {
            $sales_by_business[$row['business_id']][] = [
                'product_name' => $row['product_name'],
                'quantity' => $row['quantity'],
                'total_sales' => $row['total_sales'],
                'date' => $row['date']
            ];
        }
    }
    $stmt->close();
}

// Fetch today's sales for all businesses
$sales_data = [];
if (!empty($businesses)) {
    $query = "
        SELECT 
            p.name AS product_name,
            p.price AS product_price,
            s.quantity,
            s.total_sales,
            b.name AS business_name,
            s.date
        FROM sales s
        JOIN products p ON s.product_id = p.id
        JOIN business b ON p.business_id = b.id
        WHERE b.owner_id = ? AND s.date = ?
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $owner_id, $today);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $sales_data[] = $row;
    }
    $stmt->close();
}
?>

<script>
    const businesses = <?php echo json_encode($businesses); ?>;
    const productsByBusiness = <?php echo json_encode($products_by_business); ?>;
    const salesByBusiness = <?php echo json_encode($sales_by_business); ?>;
</script>

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

                    <!-- Sales Tables (Initially hidden) -->
                    <div id="salesContainer" style="display: none;"></div>





                    <h2 class="mt-5 mb-3"><b>Sales Log</b></h2>

                    <div id="salesLogTableSection">

                        <table class="table table-striped table-hover" id="salesLogTable">

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
                                    <th>Business/Branch</th>
                                    <th>Amount Sold</th>
                                    <th>Total Sales</th>
                                    <th>Date</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php

                                $manila_time = new DateTime('now', new DateTimeZone('Asia/Manila'));
                                $today_date = $manila_time->format('m/d/Y');
                                ?>
                                <?php if (empty($sales_data)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No Sales for
                                            <?= htmlspecialchars($today_date); ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($sales_data as $sale): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($sale['product_name']); ?></td>
                                            <td><?= htmlspecialchars($sale['business_name']); ?></td>
                                            <td><?= htmlspecialchars($sale['quantity']); ?></td>
                                            <td>₱<?= number_format($sale['total_sales'], 2, '.', ','); ?></td>

                                            <td>
                                                <?php
                                                $date = new DateTime($sale['date'], new DateTimeZone('UTC'));
                                                $date->setTimezone(new DateTimeZone('Asia/Manila'));
                                                echo htmlspecialchars($date->format('m/d/Y h:i A'));
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>

                        </table>

                        <button class="btn btn-primary mt-2 mb-5" id="salesLogTable"
                            onclick="printContent('salesLogTableSection', 'Sales Log Report')">
                            <i class="fas fa-print me-2"></i> Print Report (Sales Log)
                        </button>

                    </div>

                </div>
            </div>
        </div>
    </div>
    </div>

    <script>

    </script>
    <script src="../js/print_report.js"></script>

    <script src="../js/sidebar.js"></script>
    <script src="../js/sort_items.js"></script>

    <script src="../js/business_tracksales_filter.js"></script>
    <script src="../js/business_tracksales_add_sale.js"></script>

</body>

</html>