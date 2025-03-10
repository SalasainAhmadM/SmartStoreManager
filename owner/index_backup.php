<?php
session_start();
require_once '../conn/auth.php';
require_once '../conn/conn.php';

validateSession('owner');

$owner_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
if (isset($_GET['id']) && intval($_GET['id']) > 0) {
    $owner_id = intval($_GET['id']);
}

// Query to fetch owner details
$query = "SELECT id FROM owner WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Owner not found.');
}

$query = "SELECT * FROM owner WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();

$owner = $result->fetch_assoc();
// Check if the owner is new
$query = "SELECT is_new_owner FROM owner WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $isNewOwner = $row['is_new_owner'] == 1;
}

if (isset($_GET['status']) && $_GET['status'] === 'success') {
    echo "
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Login Successful',
                    text: 'Welcome to the dashboard!',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    " . ($isNewOwner ? "triggerAddBusinessModal();" : "") . "
                });
            });
        </script>
    ";
    unset($_SESSION['login_success']);
}


// Query to fetch business and its branches
$sql = "SELECT b.name AS business_name, br.location AS branch_location, br.business_id
        FROM business b
        JOIN branch br ON b.id = br.business_id
        WHERE b.owner_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();

// Business chart data
$businessData = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $businessData[$row['business_name']][] = $row['branch_location'];
    }
}



// Query to fetch popular products
$sql = "SELECT
    p.name AS product_name,
    COALESCE(b.name, 'Direct Business') AS business_name,
    p.type,
    p.price,
    p.description,
    SUM(s.total_sales) AS total_sales
FROM
    sales s
JOIN products p ON s.product_id = p.id
LEFT JOIN business b ON p.business_id = b.id 
WHERE s.total_sales > 0
GROUP BY p.name, b.name, p.type, p.price, p.description
ORDER BY total_sales DESC
LIMIT 10"; // Limit to top 10 products


$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

// Initialize an array to store popular products data
$popularProducts = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $popularProducts[] = $row;
    }
}



// Query to fetch activities for the owner
$sql = "SELECT id, message, created_at, status, user, user_id 
        FROM activity 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();

// Initialize an array to store activity data
$activities = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $activities[] = $row;  // Fetch all activities
    }
}



// Modify to fetch expenses for each branch
$processedData = [];
foreach ($businessData as $businessName => $branches) {
    // Initialize totals for the business-level transactions
    $businessSales = 0;
    $businessExpenses = 0;

    // Fetch total expenses directly made by the business
    $sqlBusinessExpenses = "SELECT SUM(e.amount) AS total_expenses
                            FROM expenses e
                            JOIN business b ON e.category = 'business' AND e.category_id = b.id
                            WHERE b.name = ?";
    $stmtBusinessExpenses = $conn->prepare($sqlBusinessExpenses);
    $stmtBusinessExpenses->bind_param("s", $businessName);
    $stmtBusinessExpenses->execute();
    $resultBusinessExpenses = $stmtBusinessExpenses->get_result();
    if ($resultBusinessExpenses->num_rows > 0) {
        $rowBusinessExpenses = $resultBusinessExpenses->fetch_assoc();
        $businessExpenses = $rowBusinessExpenses['total_expenses'] ?? 0;
    }

    // Fetch total sales directly made by the business
    $sqlBusinessSales = "
            SELECT SUM(s.total_sales) AS total_sales
            FROM sales s
            JOIN products p ON s.product_id = p.id
            JOIN business b ON p.business_id = b.id
            WHERE s.branch_id = 0 AND b.name = ?";
    $stmtBusinessSales = $conn->prepare($sqlBusinessSales);
    $stmtBusinessSales->bind_param("s", $businessName);
    $stmtBusinessSales->execute();
    $resultBusinessSales = $stmtBusinessSales->get_result();
    if ($resultBusinessSales->num_rows > 0) {
        $rowBusinessSales = $resultBusinessSales->fetch_assoc();
        $businessSales = $rowBusinessSales['total_sales'] ?? 0;
    }

    // Store processed data for the business-level transactions
    $processedData[$businessName]['Business/Main Branch'] = [
        'sales' => $businessSales,
        'expenses' => $businessExpenses,
    ];

    // Process branch-level data
    foreach ($branches as $branchLocation) {
        $branchSales = 0;
        $branchExpenses = 0;

        // Fetch total expenses for the branch
        $sqlBranchExpenses = "SELECT SUM(e.amount) AS total_expenses
                              FROM expenses e
                              JOIN branch br ON e.category = 'branch' AND e.category_id = br.id
                              WHERE br.location = ? AND br.business_id IN (
                                  SELECT id FROM business WHERE name = ? 
                              )";
        $stmtBranchExpenses = $conn->prepare($sqlBranchExpenses);
        $stmtBranchExpenses->bind_param("ss", $branchLocation, $businessName);
        $stmtBranchExpenses->execute();
        $resultBranchExpenses = $stmtBranchExpenses->get_result();
        if ($resultBranchExpenses->num_rows > 0) {
            $rowBranchExpenses = $resultBranchExpenses->fetch_assoc();
            $branchExpenses = $rowBranchExpenses['total_expenses'] ?? 0;
        }

        // Fetch total sales for the branch
        $sqlBranchSales = "SELECT SUM(s.total_sales) AS total_sales
                           FROM sales s
                           JOIN branch br ON s.branch_id = br.id
                           WHERE br.location = ? AND br.business_id IN (
                               SELECT id FROM business WHERE name = ? 
                           )";
        $stmtBranchSales = $conn->prepare($sqlBranchSales);
        $stmtBranchSales->bind_param("ss", $branchLocation, $businessName);
        $stmtBranchSales->execute();
        $resultBranchSales = $stmtBranchSales->get_result();
        if ($resultBranchSales->num_rows > 0) {
            $rowBranchSales = $resultBranchSales->fetch_assoc();
            $branchSales = $rowBranchSales['total_sales'] ?? 0;
        }

        // Store processed data for the branch-level transactions
        $processedData[$businessName][$branchLocation] = [
            'sales' => $branchSales,
            'expenses' => $branchExpenses,
        ];
    }
}

// Get daily sales and expenses for the past 30 days
$sqlDaily = "SELECT 
    dates.date,
    b.name as business_name,
    COALESCE(s.daily_sales, 0) as daily_sales,
    COALESCE(e.daily_expenses, 0) as daily_expenses
FROM (
    SELECT DATE(created_at) as date
    FROM sales 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    UNION
    SELECT DATE(created_at) as date
    FROM expenses
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
) dates
LEFT JOIN (
    SELECT 
        DATE(s.created_at) as date,
        b.name as business_name,
        SUM(s.total_sales) as daily_sales
    FROM sales s
    JOIN products p ON s.product_id = p.id
    JOIN business b ON p.business_id = b.id
    WHERE s.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(s.created_at), b.name
) s ON dates.date = s.date
LEFT JOIN (
    SELECT 
        DATE(e.created_at) as date,
        b.name as business_name,
        SUM(e.amount) as daily_expenses
    FROM expenses e
    JOIN business b ON e.category = 'business' AND e.category_id = b.id
    WHERE e.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(e.created_at), b.name
) e ON dates.date = e.date AND s.business_name = e.business_name
JOIN business b ON COALESCE(s.business_name, e.business_name) = b.name
ORDER BY dates.date";

$stmtDaily = $conn->prepare($sqlDaily);
$stmtDaily->execute();
$resultDaily = $stmtDaily->get_result();

$dailyData = [];
while ($row = $resultDaily->fetch_assoc()) {
    $dailyData[] = [
        'date' => $row['date'],
        'business_name' => $row['business_name'],
        'sales' => floatval($row['daily_sales']),
        'expenses' => floatval($row['daily_expenses']),
        'profit_margin' => $row['daily_sales'] > 0 ?
            (($row['daily_sales'] - $row['daily_expenses']) / $row['daily_sales']) * 100 : 0
    ];
}

// Get monthly cash flow for the past 12 months
$sqlMonthly = "SELECT 
    dates.month,
    b.name as business_name,
    COALESCE(s.monthly_sales, 0) as monthly_inflow,
    COALESCE(e.monthly_expenses, 0) as monthly_outflow
FROM (
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month
    FROM sales 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    UNION
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month
    FROM expenses
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
) dates
LEFT JOIN (
    SELECT 
        DATE_FORMAT(s.created_at, '%Y-%m') as month,
        b.name as business_name,
        SUM(s.total_sales) as monthly_sales
    FROM sales s
    JOIN products p ON s.product_id = p.id
    JOIN business b ON p.business_id = b.id
    WHERE s.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(s.created_at, '%Y-%m'), b.name
) s ON dates.month = s.month
LEFT JOIN (
    SELECT 
        DATE_FORMAT(e.created_at, '%Y-%m') as month,
        b.name as business_name,
        SUM(e.amount) as monthly_expenses
    FROM expenses e
    JOIN business b ON e.category = 'business' AND e.category_id = b.id
    WHERE e.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(e.created_at, '%Y-%m'), b.name
) e ON dates.month = e.month AND s.business_name = e.business_name
JOIN business b ON COALESCE(s.business_name, e.business_name) = b.name
ORDER BY dates.month";

$stmtMonthly = $conn->prepare($sqlMonthly);
$stmtMonthly->execute();
$resultMonthly = $stmtMonthly->get_result();

$monthlyData = [];
while ($row = $resultMonthly->fetch_assoc()) {
    $monthlyData[] = [
        'month' => $row['month'],
        'business_name' => $row['business_name'],
        'inflow' => floatval($row['monthly_inflow']),
        'outflow' => floatval($row['monthly_outflow'])
    ];
}

// Get product performance data
$sqlProducts = "SELECT 
    p.name as product_name,
    b.name as business_name,
    SUM(s.total_sales) as revenue,
    COUNT(*) as units_sold,
    SUM(s.total_sales) - (p.price * COUNT(*)) as profit
FROM sales s
JOIN products p ON s.product_id = p.id
JOIN business b ON p.business_id = b.id
WHERE s.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY p.id, p.name, b.name
ORDER BY revenue DESC";

$stmtProducts = $conn->prepare($sqlProducts);
$stmtProducts->execute();
$resultProducts = $stmtProducts->get_result();

$productData = [];
while ($row = $resultProducts->fetch_assoc()) {
    $productData[] = [
        'product_name' => $row['product_name'],
        'business_name' => $row['business_name'],
        'revenue' => floatval($row['revenue']),
        'units_sold' => intval($row['units_sold']),
        'profit' => floatval($row['profit'])
    ];
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
                    <h1><b><i class="fas fa-tachometer-alt me-2"></i> Dashboard Overview</b></h1>

                    <div class="container-fluid">
                        <div class="row">

                            <div class="col-md-5">
                                <h5 class="mt-5"><b>Select Business:</b></h5>

                                <div class="scroll-container">
                                    <?php
                                    if (empty($businessData)) {
                                        echo '<p>No business found.</p>';
                                    } else {
                                        foreach ($businessData as $businessName => $branches) {
                                            echo '<div class="col-md-12 card" data-business-name="' . $businessName . '" onclick="showBusinessData(\'' . $businessName . '\')">';
                                            echo '<h5>' . $businessName . '</h5>';

                                            // Fetch business-level sales and expenses
                                            $businessSales = $processedData[$businessName]['Business/Main Branch']['sales'] ?? 0;
                                            $businessExpenses = $processedData[$businessName]['Business/Main Branch']['expenses'] ?? 0;

                                            // Main Branch Table
                                            echo '<table class="table table-striped table-hover mt-4">';
                                            echo '<thead class="table-dark"><tr><th class="text-center" colspan="2">' . $businessName . ' Sales and Expenses/Main Branch</th></tr></thead>';
                                            echo '<thead class="table-dark"><tr><th>Total Sales (₱)</th><th>Total Expenses (₱)</th></tr></thead>';
                                            echo '<tbody>';
                                            echo '<tr>';
                                            echo '<td>' . number_format($businessSales, 2) . '</td>';
                                            echo '<td>' . number_format($businessExpenses, 2) . '</td>';
                                            echo '</tr>';
                                            echo '</tbody>';
                                            echo '</table>';

                                            // Branch-Level Table
                                            echo '<table class="table table-striped table-hover mt-4">';
                                            echo '<thead class="table-dark"><tr><th>Branches</th><th>Total Sales (₱)</th><th>Total Expenses (₱)</th></tr></thead>';
                                            echo '<tbody>';

                                            // Loop through each branch of the business and display the expenses
                                            foreach ($branches as $branchLocation) {
                                                $totalExpenses = $processedData[$businessName][$branchLocation]['expenses'];
                                                $totalSales = $processedData[$businessName][$branchLocation]['sales'];

                                                echo '<tr>';
                                                echo '<td>' . $branchLocation . '</td>';
                                                echo '<td>' . number_format($totalSales, 2) . '</td>';
                                                echo '<td>' . number_format($totalExpenses, 2) . '</td>';
                                                echo '</tr>';
                                            }

                                            echo '</tbody>';
                                            echo '</table>';
                                            echo '</div>';
                                        }
                                    }
                                    ?>
                                </div>
                            </div>




                            <div class="col-md-7">
                                <h5 class="mt-5"><b>Financial Overview:</b></h5>

                                <!-- Original Chart -->
                                <div class="chart-container mb-4">
                                    <canvas id="financialChart"></canvas>
                                </div>

                                <!-- Sales vs Expenses Chart -->
                                <div class="chart-container mb-4">
                                    <h6>Sales vs Expenses</h6>
                                    <canvas id="salesExpensesChart"></canvas>
                                </div>

                                <!-- Profit Margin Chart -->
                                <div class="chart-container mb-4">
                                    <h6>Profit Margin Trends</h6>
                                    <canvas id="profitMarginChart"></canvas>
                                </div>

                                <!-- Cash Flow Chart -->
                                <div class="chart-container mb-4">
                                    <h6>Monthly Cash Flow</h6>
                                    <canvas id="cashFlowChart"></canvas>
                                </div>

                            </div>


                        </div>


                        <div class="col-md-12 mt-5">
                            <h1><b><i class="fa-solid fa-chart-line"></i> Business Comparison</b></h1>
                            <div class="row">
                                <!-- Business Performance Chart -->
                                <div class="col-md-6">
                                    <div class="chart-container mb-4" style="height: 400px;">
                                        <h5 class="mt-5"><b>Business Performance Comparison</b></h5>
                                        <canvas id="businessPerformanceChart"></canvas>
                                    </div>
                                </div>

                                <!-- Revenue Contribution Chart -->
                                <div class="col-md-6">
                                    <div class="chart-container mb-4" style="height: 400px;">
                                        <h5 class="mt-5"><b>Revenue Contribution by Business</b></h5>
                                        <canvas id="revenueContributionChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 mt-5">
                            <h1><b><i class="fa-solid fa-box"></i> Product/Service Analysis</b></h1>
                            <div class="row">
                                <!-- Top-Selling Products Chart -->
                                <div class="col-md-6">
                                    <div class="chart-container mb-4" style="height: 400px;">
                                        <h5 class="mt-5"><b>Top-Selling Products/Services</b></h5>
                                        <canvas id="topProductsChart"></canvas>
                                    </div>
                                </div>

                                <!-- Low-Performing Products Chart -->
                                <div class="col-md-6">
                                    <div class="chart-container mb-4" style="height: 400px;">
                                        <h5 class="mt-5"><b>Low-Performing Products/Services</b></h5>
                                        <canvas id="lowProductsChart"></canvas>
                                    </div>
                                </div>

                                <!-- Product Profitability Chart -->
                                <div class="col-md-12">
                                    <div class="chart-container mb-4" style="height: 400px;">
                                        <h5 class="mt-5 mb-3"><b>Product/Service Profitability Analysis</b></h5>
                                        <canvas id="productProfitabilityChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Popular Products Section -->
                        <!-- <div class="col-md-12 mt-5">
                            <h1><b><i class="fa-solid fa-lightbulb"></i> Insights</b></h1>
                            <div class="col-md-12 dashboard-content">

                                <div class="mb-5 position-relative">
                                    <button id="uploadDataButton" class="btn btn-success"><i class="fa-solid fa-upload"></i> Upload Data</button>
                                </div>

                                <?php
                                if (isset($_GET['data'])) {
                                    $data = json_decode($_GET['data'], true);
                                    $yearMonth = isset($_GET['yearMonth']) ? htmlspecialchars($_GET['yearMonth']) : 'Unknown Period';

                                    if (!empty($data)) {
                                        echo "<h3 class='mb-3'>Sales Report for $yearMonth</h3>";

                                        echo "<div class='scrollable-table'>";
                                        echo "<table class='table mb-3'>";
                                        echo "<thead class='table-dark position-sticky top-0'>
                                                <tr>
                                                    <th>Business <button class='btn text-white'><i class='fas fa-sort'></i></button></th>
                                                    <th>Branches <button class='btn text-white'><i class='fas fa-sort'></i></button></th>
                                                    <th>Total Sales (₱) <button class='btn text-white'><i class='fas fa-sort'></i></button></th>
                                                    <th>Total Expenses (₱) <button class='btn text-white'><i class='fas fa-sort'></i></button></th>
                                                </tr>
                                                </thead>";
                                        foreach ($data as $row) {
                                            echo "<tr>";
                                            echo "<td>" . htmlspecialchars($row['business']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row['branches']) . "</td>";
                                            echo "<td>" . htmlspecialchars(number_format($row['sales'], 2)) . "</td>";
                                            echo "<td>" . htmlspecialchars(number_format($row['expenses'], 2)) . "</td>";
                                            echo "</tr>";
                                        }
                                        echo "</table>";
                                        echo "</div>";
                                    } else {
                                        echo "<p>No data available.</p>";
                                    }
                                } else {
                                    echo "<p>No data received.</p>";
                                }
                                ?>


                                <button class="btn btn-success mb-5 mt-3" type="submit">
                                    <i class="fa-solid fa-file"></i> Generate Insight
                                </button>

                                <div>
                                    <h5><b>Predicted Growth:</b></h5>
                                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi tincidunt
                                        tellus quis ligula semper, vitae bibendum felis lacinia. Donec eleifend
                                        tellus ac massa malesuada, a pellentesque dolor scelerisque. Sed feugiat
                                        felis vel odio condimentum aliquet. Nulla sit amet urna sed est elementum
                                        dapibus non ac mauris. Aenean nec est diam. Maecenas a nisi ut nibh luctus
                                        porttitor. Vestibulum pretium auctor condimentum.</p>
                                </div>
                                <div>
                                    <h5><b>Advice:</b></h5>
                                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi tincidunt
                                        tellus quis ligula semper, vitae bibendum felis lacinia. Donec eleifend
                                        tellus ac massa malesuada, a pellentesque dolor scelerisque. Sed feugiat
                                        felis vel odio condimentum aliquet. Nulla sit amet urna sed est elementum
                                        dapibus non ac mauris. Aenean nec est diam. Maecenas a nisi ut nibh luctus
                                        porttitor. Vestibulum pretium auctor condimentum.</p>
                                </div>
                            </div>
                        </div> -->

                        <div id="popularProductsSection">
                            <div class="col-md-12 mt-5">
                                <h1 class="section-title">
                                    <b><i class="fas fa-boxes icon"></i> Popular Products</b>
                                </h1>
                                <div class="col-md-12 dashboard-content">
                                    <table class="table table-hover" id="product-table">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Product <button class="btn text-white"><i
                                                            class="fas fa-sort"></i></button></th>
                                                <th>Business <button class="btn text-white"><i
                                                            class="fas fa-sort"></i></button></th>
                                                <th>Type <button class="btn text-white"><i
                                                            class="fas fa-sort"></i></button></th>
                                                <th>Price <button class="btn text-white"><i
                                                            class="fas fa-sort"></i></button></th>
                                                <th>Description <button class="btn text-white"><i
                                                            class="fas fa-sort"></i></button></th>
                                                <th>Total Sales <button class="btn text-white"><i
                                                            class="fas fa-sort"></i></button></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($popularProducts as $product): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($product['business_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($product['type']); ?></td>
                                                    <td><?php echo '₱' . number_format($product['price'], 2); ?></td>
                                                    <td><?php echo htmlspecialchars($product['description']); ?></td>
                                                    <td><?php echo '₱' . number_format($product['total_sales'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>

                                    <button class="btn btn-primary mt-2 mb-5" id="printPopularProducts"
                                        onclick="printTable('product-table', 'Popular Products')">
                                        <i class="fas fa-print me-2"></i> Print Report (Popular Products)
                                    </button>
                                </div>
                            </div>
                        </div>



                        <div id="recentActivitiesSection">
                            <div class="col-md-12 mt-5">
                                <h1><b><i class="fas fa-bell"></i> Activity Log</b></h1>
                                <div class="col-md-12 dashboard-content">
                                    <table class="table table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Activity</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($activities)): ?>
                                                <tr>
                                                    <td colspan="3" class="text-center">No activities found.</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($activities as $activity): ?>
                                                    <tr>
                                                        <td>
                                                            <?php
                                                            $message = htmlspecialchars($activity['message']);

                                                            // Define the FontAwesome icon based on the message
                                                            if (strpos($message, 'Manager Added') !== false || strpos($message, 'Manager Deleted') !== false) {
                                                                echo '<i class="fas fa-user-plus"></i> ' . $message;  // Manager icon
                                                            } elseif (strpos($message, 'Sale Added') !== false) {
                                                                echo '<i class="fas fa-cart-plus"></i> ' . $message;  // Sale icon
                                                            } elseif (strpos($message, 'Expense Added') !== false) {
                                                                echo '<i class="fas fa-dollar-sign"></i> ' . $message;  // Expense icon
                                                            } else {
                                                                echo $message;  // No icon if no match
                                                            }
                                                            ?>
                                                        </td>

                                                        <td><?php echo date('F j, Y, g:i a', strtotime($activity['created_at'])); ?>
                                                        </td>
                                                        <td>
                                                            <?php echo $activity['status']; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
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


    </div>
    <!-- <script>
        window.onload = function () {
            Swal.fire({
                icon: 'success',
                title: 'Login Successful',
                text: 'Welcome!',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                <?php unset($_SESSION['login_success']); ?>
                <?php if ($isNewOwner): ?>
                    triggerAddBusinessModal();
                <?php endif; ?>
            });
        };
    </script> -->

    <script>
        document.getElementById('uploadDataButton').addEventListener('click', function () {
            Swal.fire({
                title: 'Upload or Download Data',
                html: `
                <div class="mt-3 mb-3 position-relative">
                    <form action="../import_excel.php" method="POST" enctype="multipart/form-data" class="btn btn-success p-3">
                        <i class="fa-solid fa-upload"></i>
                        <label for="file" class="mb-2">Upload Data:</label>
                        <input type="file" name="file" id="file" accept=".xlsx, .xls" class="form-control mb-2">
                        <input type="submit" value="Upload Excel" class="form-control">
                    </form>
                    <form action="../export_excel.php" method="POST" class="top-0 end-0 mt-2 me-2">
                        <button class="btn btn-success" type="submit">
                            <i class="fa-solid fa-download"></i> Download Data Template
                        </button>
                    </form>
                </div>
                `,
                showConfirmButton: false, // Remove default confirmation button
                customClass: {
                    popup: 'swal2-modal-wide' // Optional for larger modals
                }
            });
        });
    </script>


    <script>
        const ownerId = <?= json_encode(isset($_GET['id']) ? $_GET['id'] : $_SESSION['user_id']); ?>;

        const businessData = <?php echo json_encode($processedData); ?>;

        function triggerAddBusinessModal(ownerId) {
            Swal.fire({
                title: 'Add New Business',
                html: `
        <div>
            <input type="text" id="business-name" class="form-control mb-2" placeholder="Business Name">
            <input type="text" id="business-description" class="form-control mb-2" placeholder="Business Description">
            <input type="number" id="business-asset" class="form-control mb-2" placeholder="Asset Size">
            <input type="number" id="employee-count" class="form-control mb-2" placeholder="Number of Employees">
        </div>
        `,
                confirmButtonText: 'Add Business',
                showCancelButton: true,
                cancelButtonText: 'Skip'
            }).then((result) => {
                if (result.isConfirmed) {
                    const businessName = document.getElementById('business-name').value.trim();
                    const businessDescription = document.getElementById('business-description').value.trim();
                    const businessAsset = document.getElementById('business-asset').value.trim();
                    const employeeCount = document.getElementById('employee-count').value.trim();

                    if (!businessName || !businessAsset || !employeeCount) {
                        Swal.fire('Error', 'Please fill in all required fields.', 'error');
                        return;
                    }

                    const formData = new FormData();
                    formData.append('name', businessName);
                    formData.append('description', businessDescription);
                    formData.append('asset', businessAsset);
                    formData.append('employeeCount', employeeCount);
                    formData.append('owner_id', ownerId || <?= json_encode($_SESSION['user_id']); ?>);

                    fetch('../endpoints/business/add_business_prompt.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Success', data.message, 'success').then(() => {
                                    const url = new URL(window.location.href);
                                    url.search = '';
                                    history.replaceState(null, '', url);
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error', data.message, 'error');
                            }
                        })
                        .catch(err => {
                            Swal.fire('Error', 'An unexpected error occurred.', 'error');
                            console.error(err);
                        });
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    // Trigger update to set is_new_owner = 0
                    fetch('../endpoints/business/skip_business_prompt.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            owner_id: ownerId || <?= json_encode($_SESSION['user_id']); ?>
                        })
                    })
                }
            });
        }
    </script>


    <script>
        function printTable(tableId, title) {
            const table = document.getElementById(tableId);

            // Create a new window for printing
            const printWindow = window.open('', '_blank', 'width=800,height=600');
            printWindow.document.open();
            printWindow.document.write(`
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Print Report</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    margin: 20px;
                }
                h1 {
                    text-align: center;
                    margin-bottom: 20px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                }
                table, th, td {
                    border: 1px solid black;
                }
                th, td {
                    padding: 10px;
                    text-align: left;
                }
                thead {
                    background-color: #333;
                    color: #fff;
                }
                tfoot {
                    background-color: #f1f1f1;
                    font-weight: bold;
                }
                button, .btn, .fas.fa-sort {
                    display: none; /* Hide sort icons and buttons in print */
                }
            </style>
        </head>
        <body>
            <h1>${title}</h1>
            ${table.outerHTML}               
        </body>
        </html>
    `);
            printWindow.print();
            printWindow.document.close();
        }

        // Attach event listeners to print buttons
        document.getElementById('printPopularProducts').addEventListener('click', () => {
            printTable('product-table', 'Popular Products Report');
        });

        document.getElementById('printRecentActivities').addEventListener('click', () => {
            printTable('recent-activities-table', 'Recent Activities Report');
        });
    </script>



    <script>
        // Prepare data for all charts
        var chartData = <?php echo json_encode($processedData); ?>;
        var dailyData = <?php echo json_encode($dailyData); ?>;
        var monthlyData = <?php echo json_encode($monthlyData); ?>;
        var productData = <?php echo json_encode($productData); ?>;
    </script>
    <script src="../js/chart.js"></script>

    <script src="../js/sidebar.js"></script>
    <script src="../js/sort_items.js"></script>

</body>

</html>