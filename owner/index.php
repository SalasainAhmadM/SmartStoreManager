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
$sql = "SELECT b.id AS business_id, b.name AS business_name, br.location AS branch_location
        FROM business b
        LEFT JOIN branch br ON b.id = br.business_id
        WHERE b.owner_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();

// Business chart data
$businessData = [];
while ($row = $result->fetch_assoc()) {
    $businessData[$row['business_name']][] = $row['branch_location'] ?? 'No Branch for this Business';
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
AND b.owner_id = ?
GROUP BY p.name, b.name, p.type, p.price, p.description
ORDER BY total_sales DESC
LIMIT 10"; // Limit to top 10 products

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $owner_id);
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
        $activities[] = $row; // Fetch all activities
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
WHERE b.name = ? AND b.owner_id = ?";
    $stmtBusinessExpenses = $conn->prepare($sqlBusinessExpenses);
    $stmtBusinessExpenses->bind_param("si", $businessName, $owner_id);
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
WHERE s.branch_id = 0 AND b.name = ? AND b.owner_id = ?";
    $stmtBusinessSales = $conn->prepare($sqlBusinessSales);
    $stmtBusinessSales->bind_param("si", $businessName, $owner_id);
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
JOIN business b ON br.business_id = b.id
WHERE br.location = ? AND b.name = ? AND b.owner_id = ?";
        $stmtBranchExpenses = $conn->prepare($sqlBranchExpenses);
        $stmtBranchExpenses->bind_param("ssi", $branchLocation, $businessName, $owner_id);
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
JOIN business b ON br.business_id = b.id
WHERE br.location = ? AND b.name = ? AND b.owner_id = ?";
        $stmtBranchSales = $conn->prepare($sqlBranchSales);
        $stmtBranchSales->bind_param("ssi", $branchLocation, $businessName, $owner_id);
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

// Get daily sales and expenses for the past 30 days for the owner
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
AND b.owner_id = ?
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
AND b.owner_id = ?
GROUP BY DATE(e.created_at), b.name
) e ON dates.date = e.date AND s.business_name = e.business_name
JOIN business b ON COALESCE(s.business_name, e.business_name) = b.name
WHERE b.owner_id = ?
ORDER BY dates.date";

$stmtDaily = $conn->prepare($sqlDaily);
$stmtDaily->bind_param("iii", $owner_id, $owner_id, $owner_id);
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

// Get monthly cash flow for the past 12 months for the owner
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
AND b.owner_id = ?
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
AND b.owner_id = ?
GROUP BY DATE_FORMAT(e.created_at, '%Y-%m'), b.name
) e ON dates.month = e.month AND s.business_name = e.business_name
JOIN business b ON COALESCE(s.business_name, e.business_name) = b.name
WHERE b.owner_id = ?
ORDER BY dates.month";

$stmtMonthly = $conn->prepare($sqlMonthly);
$stmtMonthly->bind_param("iii", $owner_id, $owner_id, $owner_id);
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

// Get product performance data for the owner
$sqlProducts = "SELECT
p.name as product_name,
b.name as business_name,
SUM(s.total_sales) as revenue,
COUNT(*) as units_sold,
SUM(s.total_sales) - (p.price * COUNT(*)) as profit
FROM sales s
JOIN products p ON s.product_id = p.id
JOIN business b ON p.business_id = b.id
WHERE b.owner_id = ?
AND s.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY p.id, p.name, b.name
ORDER BY revenue DESC";

$stmtProducts = $conn->prepare($sqlProducts);
$stmtProducts->bind_param("i", $owner_id);
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
// 6
// Fetch Category-Wise Expenses
$categoryQuery = "
SELECT category, SUM(amount) AS total_amount
FROM expenses
WHERE owner_id = $owner_id
GROUP BY category
";
$categoryResult = $conn->query($categoryQuery);

$categoryData = [];
while ($row = $categoryResult->fetch_assoc()) {
    $categoryData[$row['category']] = '₱' . number_format($row['total_amount'], 2);
}

// Fetch Recurring vs One-Time Expenses by Month
$recurringQuery = "
SELECT
month,
SUM(amount) AS recurring,
SUM(DISTINCT amount) AS oneTime
FROM expenses
WHERE owner_id = $owner_id
GROUP BY month
";
$recurringResult = $conn->query($recurringQuery);

$recurringData = [];
while ($row = $recurringResult->fetch_assoc()) {
    $recurringData[$row['month']] = [
        'recurring' => '₱' . number_format($row['recurring'], 2),
        'oneTime' => '₱' . number_format($row['oneTime'], 2)
    ];
}

// Structure Data for Charts
$expenseDataBreakdown = [
    'categories' => $categoryData,
    'recurringByMonth' => $recurringData
];

// 7
// Get the business ID of the logged-in owner
$businessQuery = "SELECT id FROM business WHERE owner_id = $owner_id";
$businessResult = $conn->query($businessQuery);
$business = $businessResult->fetch_assoc();
$business_id = $business['id'] ?? null; // Avoid errors if no business is found

// Initialize arrays
$products = [];
$stockLevels = [];
$salesTurnover = [];
$salesByMonth = [];

if ($business_id) {
    // Fetch Products & Their Stock Levels
    $productQuery = "
    SELECT p.id, p.name, p.price, COALESCE(SUM(s.quantity), 0) AS total_sold
    FROM products p
    LEFT JOIN sales s ON p.id = s.product_id
    WHERE p.business_id = $business_id
    GROUP BY p.id, p.name, p.price
    ORDER BY total_sold DESC
    LIMIT 10"; // Show top 10 products

    $productResult = $conn->query($productQuery);

    if ($productResult->num_rows > 0) {
        while ($row = $productResult->fetch_assoc()) {
            $products[] = $row['name'];
            $stockLevels[] = rand(10, 100); // Placeholder stock levels (Replace with actual stock table if available)
            $salesTurnover[$row['name']] = $row['total_sold'];
        }
    }

    // Fetch Sales Data for Turnover Chart
    $salesQuery = "
    SELECT DATE_FORMAT(s.date, '%Y-%m') AS sale_month, p.name, SUM(s.quantity) AS total_sold
    FROM sales s
    JOIN products p ON s.product_id = p.id
    WHERE p.business_id = $business_id
    GROUP BY sale_month, p.name
    ORDER BY sale_month ASC";

    $salesResult = $conn->query($salesQuery);

    if ($salesResult->num_rows > 0) {
        while ($row = $salesResult->fetch_assoc()) {
            $salesByMonth[$row['sale_month']][$row['name']] = $row['total_sold'];
        }
    }
}

// Prepare Data for JSON Output
$inventoryData = [
    'products' => $products,
    'stockLevels' => $stockLevels,
    'salesTurnover' => $salesTurnover,
    'salesByMonth' => $salesByMonth
];

// 8
// Fetch Total Sales
$salesQuery = "
SELECT SUM(total_sales) AS total_sales
FROM sales
WHERE product_id IN (SELECT id FROM products WHERE business_id IN (SELECT id FROM business WHERE owner_id = $owner_id))
";
$salesResult = $conn->query($salesQuery);
$salesData = $salesResult->fetch_assoc();
$totalSales = $salesData['total_sales'] ?? 0;

// Fetch Total Expenses
$expensesQuery = "
SELECT SUM(amount) AS total_expenses
FROM expenses
WHERE owner_id = $owner_id
";
$expensesResult = $conn->query($expensesQuery);
$expensesData = $expensesResult->fetch_assoc();
$totalExpenses = $expensesData['total_expenses'] ?? 0;

// Fetch Revenue Growth (Current vs Previous Month)
$revenueGrowthQuery = "
SELECT
SUM(CASE WHEN MONTH(date) = MONTH(CURRENT_DATE()) THEN total_sales ELSE 0 END) AS currentMonthSales,
SUM(CASE WHEN MONTH(date) = MONTH(CURRENT_DATE()) - 1 THEN total_sales ELSE 0 END) AS previousMonthSales
FROM sales
WHERE product_id IN (SELECT id FROM products WHERE business_id IN (SELECT id FROM business WHERE owner_id = $owner_id))
";
$revenueGrowthResult = $conn->query($revenueGrowthQuery);
$revenueGrowthData = $revenueGrowthResult->fetch_assoc();

$currentMonthSales = $revenueGrowthData['currentMonthSales'] ?? 0;
$previousMonthSales = $revenueGrowthData['previousMonthSales'] ?? 0;
$growthRate = $previousMonthSales > 0 ? (($currentMonthSales - $previousMonthSales) / $previousMonthSales) * 100 : 0;

// Calculate KPIs
$grossProfitPercentage = $totalSales > 0 ? (($totalSales - $totalExpenses) / $totalSales) * 100 : 0;
$roi = $totalExpenses > 0 ? (($totalSales - $totalExpenses) / $totalExpenses) * 100 : 0;

// Format as currency (₱)
function formatCurrency($value)
{
    return "₱" . number_format($value, 2);
}

// 9
// Fetch monthly sales for past 12 months
$salesForecastQuery = "
SELECT DATE_FORMAT(date, '%Y-%m') AS month, SUM(total_sales) AS total_sales
FROM sales
WHERE product_id IN (
SELECT id FROM products WHERE business_id IN (
SELECT id FROM business WHERE owner_id = ?
)
)
GROUP BY month
ORDER BY month ASC
";
$stmt = $conn->prepare($salesForecastQuery);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();

$salesData = [];
while ($row = $result->fetch_assoc()) {
    $salesData[$row['month']] = $row['total_sales'];
}

// Fetch monthly expenses for past 12 months
$expenseForecastQuery = "
SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, SUM(amount) AS total_expenses
FROM expenses
WHERE owner_id = ?
GROUP BY month
ORDER BY month ASC
";
$stmt = $conn->prepare($expenseForecastQuery);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();

$expenseData = [];
while ($row = $result->fetch_assoc()) {
    $expenseData[$row['month']] = $row['total_expenses'];
}

// Convert PHP arrays to JSON for JavaScript
$salesJson = json_encode($salesData);
$expensesJson = json_encode($expenseData);

// 10
// Fetch total monthly expenses for the owner
$expenseQuery = "
SELECT month, SUM(amount) AS total_expenses
FROM expenses
WHERE owner_id = ?
GROUP BY month
ORDER BY month ASC
";
$stmt = $conn->prepare($expenseQuery);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();

$expenseData = [];
$thresholds = [];
$breachMonths = [];

while ($row = $result->fetch_assoc()) {
    $totalExpense = $row['total_expenses'];
    $month = $row['month'];

    // Set dynamic threshold (e.g., 80% of the total monthly expenses)
    $threshold = $totalExpense * 0.8;
    $thresholds[$month] = $threshold;

    $expenseData[$month] = $totalExpense;

    // Check if the expense exceeds the threshold
    if ($totalExpense > $threshold) {
        $breachMonths[] = $month;
    }
}

// Convert data to JSON for JavaScript
$expensesJson = json_encode($expenseData);
$thresholdsJson = json_encode($thresholds);
$breachMonthsJson = json_encode($breachMonths);




// Get customer demographics data
$sqlDemographics = "SELECT
br.location,
p.name as product_name,
COUNT(*) as purchase_count,
SUM(s.total_sales) as total_revenue
FROM sales s
JOIN branch br ON s.branch_id = br.id
JOIN products p ON s.product_id = p.id
JOIN business b ON p.business_id = b.id
WHERE b.owner_id = ?
AND s.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY br.location, p.name
ORDER BY total_revenue DESC
LIMIT 10";

$stmtDemographics = $conn->prepare($sqlDemographics);
$stmtDemographics->bind_param("i", $owner_id);
$stmtDemographics->execute();
$resultDemographics = $stmtDemographics->get_result();

$demographicsData = [];
while ($row = $resultDemographics->fetch_assoc()) {
    $demographicsData[] = [
        'location' => $row['location'],
        'product_name' => $row['product_name'],
        'purchase_count' => intval($row['purchase_count']),
        'total_revenue' => floatval($row['total_revenue'])
    ];
}

// Get trend analysis data for current year
$sqlTrends = "WITH RECURSIVE months AS (
SELECT DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 11 MONTH), '%Y-%m-01') as date
UNION ALL
SELECT DATE_ADD(date, INTERVAL 1 MONTH)
FROM months
WHERE DATE_ADD(date, INTERVAL 1 MONTH) <= CURDATE() ), monthly_data AS ( SELECT DATE_FORMAT(m.date, '%Y-%m' ) as month,
    COALESCE(SUM(DISTINCT s.total_sales), 0) as monthly_sales, COALESCE(SUM(DISTINCT e.amount), 0) as monthly_expenses,
    COALESCE(SUM(DISTINCT (s.total_sales - (s.quantity * p.price))), 0) as monthly_profit FROM months m LEFT JOIN sales
    s ON DATE_FORMAT(s.created_at, '%Y-%m' )=DATE_FORMAT(m.date, '%Y-%m' ) LEFT JOIN products p ON s.product_id=p.id
    LEFT JOIN business b ON p.business_id=b.id LEFT JOIN branch br ON s.branch_id=br.id LEFT JOIN expenses e ON
    DATE_FORMAT(e.created_at, '%Y-%m' )=DATE_FORMAT(m.date, '%Y-%m' ) AND ((e.category='business' AND
    e.category_id=b.id) OR (e.category='branch' AND e.category_id=br.id)) WHERE b.owner_id=? GROUP BY m.date ) SELECT *
    FROM monthly_data ORDER BY month ASC";
$stmtTrends = $conn->prepare($sqlTrends);
$stmtTrends->bind_param("i", $owner_id);
$stmtTrends->execute();
$resultTrends = $stmtTrends->get_result();

$trendData = [];
while ($row = $resultTrends->fetch_assoc()) {
    $trendData[] = [
        'month' => $row['month'],
        'sales' => floatval($row['monthly_sales']),
        'expenses' => floatval($row['monthly_expenses']),
        'profit' => floatval($row['monthly_profit'])
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                                            $businessSales = $processedData[$businessName]['Business/Main Branch']['sales'] ?? null;
                                            $businessExpenses = $processedData[$businessName]['Business/Main Branch']['expenses'] ?? null;

                                            // Main Branch Table
                                            if ($businessSales !== null || $businessExpenses !== null) {
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
                                            }

                                            // Branch-Level Table
                                            echo '<table class="table table-striped table-hover mt-4">';
                                            echo '<thead class="table-dark"><tr><th>Branches</th><th>Total Sales (₱)</th><th>Total Expenses (₱)</th></tr></thead>';
                                            echo '<tbody>';

                                            // Loop through each branch of the business and display the expenses
                                            foreach ($branches as $branchLocation) {
                                                if ($branchLocation === 'No Branch for this Business') {
                                                    echo '<tr><td colspan="3" class="text-center">No Branch for this Business</td></tr>';
                                                } else {
                                                    $totalSales = $processedData[$businessName][$branchLocation]['sales'] ?? null;
                                                    $totalExpenses = $processedData[$businessName][$branchLocation]['expenses'] ?? null;
                                                    echo '<tr>';
                                                    echo '<td>' . $branchLocation . '</td>';
                                                    if ($totalSales !== null || $totalExpenses !== null) {
                                                        echo '<td>' . number_format($totalSales, 2) . '</td>';
                                                        echo '<td>' . number_format($totalExpenses, 2) . '</td>';
                                                    } else {
                                                        echo '<td colspan="2" class="text-center">No Data Available</td>';
                                                    }
                                                    echo '</tr>';
                                                }
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

                                <!-- 6 -->

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
                            <h1><b><i class="fa-solid fa-chart-pie"></i> Expense Breakdown</b></h1>
                            <div class="row">
                                <!-- Category-Wise Expenses -->
                                <div class="col-md-6">
                                    <div class="chart-container mb-4" style="height: 400px;">
                                        <h5 class="mt-5"><b>Category-Wise Expenses</b></h5>
                                        <canvas id="categoryExpenseChart"></canvas>
                                    </div>
                                </div>

                                <!-- Recurring vs. One-Time Expenses -->
                                <div class="col-md-6">
                                    <div class="chart-container mb-4" style="height: 400px;">
                                        <h5 class="mt-5"><b>Recurring vs. One-Time Expenses</b></h5>
                                        <canvas id="recurringExpenseChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 mt-5">
                            <h1><b><i class="fa-solid fa-boxes-stacked"></i> Inventory Management</b></h1>
                            <div class="row">
                                <!-- Stock Levels Chart -->
                                <div class="col-md-6">
                                    <div class="chart-container mb-4" style="height: 400px;">
                                        <h5 class="mt-5"><b>Stock Levels of Top Products</b></h5>
                                        <canvas id="stockLevelChart"></canvas>
                                    </div>
                                </div>

                                <!-- Stock Turnover Chart -->
                                <div class="col-md-6">
                                    <div class="chart-container mb-4" style="height: 400px;">
                                        <h5 class="mt-5"><b>Stock Turnover Over Time</b></h5>
                                        <canvas id="stockTurnoverChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 mt-5">
                            <h1><b><i class="fa-solid fa-chart-line"></i> Key Performance Indicators (KPIs)</b></h1>
                            <div class="row">
                                <!-- Gross Profit Percentage -->
                                <div class="col-md-4">
                                    <div class="card text-center shadow p-4">
                                        <h5><b>Gross Profit %</b></h5>
                                        <h3 class="text-success">
                                            <b><?php echo number_format($grossProfitPercentage, 2); ?>%</b>
                                        </h3>
                                    </div>
                                </div>

                                <!-- Revenue Growth Rate -->
                                <div class="col-md-4">
                                    <div class="card text-center shadow p-4">
                                        <h5><b>Revenue Growth Rate</b></h5>
                                        <h3 class="<?php echo $growthRate >= 0 ? 'text-primary' : 'text-danger'; ?>">
                                            <b><?php echo number_format($growthRate, 2); ?>%</b>
                                        </h3>
                                    </div>
                                </div>

                                <!-- Return on Investment (ROI) -->
                                <div class="col-md-4">
                                    <div class="card text-center shadow p-4">
                                        <h5><b>Return on Investment (ROI)</b></h5>
                                        <h3 class="text-info"><b><?php echo number_format($roi, 2); ?>%</b></h3>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-12 mt-5">
                            <h1><b><i class="fa-solid fa-chart-line"></i> Forecasting & Predictions</b></h1>
                            <div class="row">
                                <!-- Sales Forecast -->
                                <div class="col-md-6">
                                    <div class="chart-container mb-4" style="height: 400px;">
                                        <h5><b>Sales Forecast</b></h5>
                                        <canvas id="salesForecastChart"></canvas>
                                    </div>
                                </div>

                                <!-- Expense Forecast -->
                                <div class="col-md-6">
                                    <div class="chart-container mb-4" style="height: 400px;">
                                        <h5><b>Expense Forecast</b></h5>
                                        <canvas id="expenseForecastChart"></canvas>
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

                                <!-- Customer Demographics -->
                                <div class="col-md-12 mt-5">
                                    <h1><b><i class="fa-solid fa-users"></i> Customer Demographics</b></h1>
                                    <div class="chart-container mb-4" style="height: 400px;">
                                        <h5 class="mt-5"><b>Top Products by Location</b></h5>
                                        <canvas id="demographicsChart"></canvas>
                                    </div>
                                </div>

                                <!-- Trend Analysis -->
                                <div class="col-md-12 mt-5">
                                    <h1><b><i class="fa-solid fa-chart-line"></i> Trend Analysis</b></h1>
                                    <div class="row">
                                        <!-- Seasonal Trends Chart -->
                                        <div class="col-md-6">
                                            <div class="chart-container mb-4" style="height: 400px;">
                                                <h5 class="mt-5"><b>Seasonal Trends</b></h5>
                                                <canvas id="seasonalTrendsChart"></canvas>
                                            </div>
                                        </div>

                                        <!-- Growth Rate Chart -->
                                        <div class="col-md-6">
                                            <div class="chart-container mb-4" style="height: 400px;">
                                                <h5 class="mt-5"><b>Growth Rate Analysis</b></h5>
                                                <canvas id="growthRateChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

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
                                            <?php if (!empty($popularProducts)): ?>
                                                <?php foreach ($popularProducts as $product): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($product['business_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($product['type']); ?></td>
                                                        <td><?php echo '₱' . number_format($product['price'], 2); ?></td>
                                                        <td><?php echo htmlspecialchars($product['description']); ?></td>
                                                        <td><?php echo '₱' . number_format($product['total_sales'], 2); ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="6" style="text-align: center;">No Popular Products
                                                        Found
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>

                                    </table>

                                    <button class="btn btn-primary mt-2 mb-5" id="printPopularProducts"
                                        onclick="printTable('product-table', 'Popular Products')">
                                        <i class="fas fa-print me-2"></i> Print Report (Popular Products)
                                    </button>
                                </div>
                            </div>
                        </div>
                        <!--  -->
                        <div class="col-md-12 mt-5">
                            <h1><b><i class="fa-solid fa-exclamation-triangle"></i> Alerts & Thresholds</b></h1>
                            <div class="row">
                                <!-- Expense Threshold Bar Chart -->
                                <div class="col-md-8">
                                    <div class="chart-container mb-4" style="height: 400px;">
                                        <h5><b>Expense Threshold Breach</b></h5>
                                        <canvas id="expenseThresholdChart"></canvas>
                                    </div>
                                </div>

                                <!-- Breach Notifications -->
                                <div class="col-md-4">
                                    <div class="alert-container">
                                        <h5><b>⚠️ Threshold Breaches</b></h5>
                                        <ul id="breachList" class="list-group"></ul>
                                    </div>
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

        // 6
        var expenseData = <?php echo json_encode($expenseDataBreakdown); ?>;

        // Pie Chart for Category-Wise Expenses
        var categoryLabels = Object.keys(expenseData.categories);
        var categoryValues = Object.values(expenseData.categories).map(value => parseFloat(value.replace('₱', '').replace(',', '')));

        var ctx1 = document.getElementById('categoryExpenseChart').getContext('2d');
        new Chart(ctx1, {
            type: 'pie',
            data: {
                labels: categoryLabels,
                datasets: [{
                    data: categoryValues,
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4CAF50', '#9C27B0'],
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function (tooltipItem) {
                                return '₱' + tooltipItem.raw.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Stacked Bar Chart for Recurring vs. One-Time Expenses per Month
        var months = Object.keys(expenseData.recurringByMonth);
        var recurringValues = months.map(m => parseFloat(expenseData.recurringByMonth[m].recurring.replace('₱', '').replace(',', '')));
        var oneTimeValues = months.map(m => parseFloat(expenseData.recurringByMonth[m].oneTime.replace('₱', '').replace(',', '')));

        var ctx2 = document.getElementById('recurringExpenseChart').getContext('2d');
        new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [{
                    label: 'Recurring Expenses',
                    data: recurringValues,
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgb(75, 192, 192)',
                    borderWidth: 1
                }, {
                    label: 'One-Time Expenses',
                    data: oneTimeValues,
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgb(255, 99, 132)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        stacked: true
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function (tooltipItem) {
                                return '₱' + tooltipItem.raw.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // 7
        var inventoryData = <?php echo json_encode($inventoryData); ?>;

        // Bar Chart: Stock Levels
        var ctx1 = document.getElementById('stockLevelChart').getContext('2d');
        new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: inventoryData.products,
                datasets: [{
                    label: 'Stock Levels',
                    data: inventoryData.stockLevels,
                    backgroundColor: 'rgba(153, 102, 255, 0.5)',
                    borderColor: 'rgb(153, 102, 255)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Line Chart: Stock Turnover Over Time
        var months = Object.keys(inventoryData.salesByMonth);
        var productNames = inventoryData.products;
        var datasets = productNames.map(product => ({
            label: product,
            data: months.map(month => inventoryData.salesByMonth[month]?.[product] || 0),
            borderWidth: 2,
            fill: false
        }));

        var ctx2 = document.getElementById('stockTurnoverChart').getContext('2d');
        new Chart(ctx2, {
            type: 'line',
            data: {
                labels: months,
                datasets: datasets
            },
            options: {
                responsive: true,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function (tooltipItem) {
                                return tooltipItem.dataset.label + ': ' + tooltipItem.raw + ' units sold';
                            }
                        }
                    }
                }
            }
        });

        // 9
        document.addEventListener("DOMContentLoaded", function () {
            // Parse JSON data from PHP
            const salesData = JSON.parse('<?php echo $salesJson; ?>');
            const expenseData = JSON.parse('<?php echo $expensesJson; ?>');

            // Convert object to arrays for Chart.js
            const labels = Object.keys(salesData);
            const salesValues = Object.values(salesData);
            const expenseValues = Object.values(expenseData);

            // Predict next 3 months using simple average
            function predictFutureValues(values) {
                let sum = values.reduce((a, b) => a + b, 0);
                let avg = sum / values.length;
                return [avg * 1.05, avg * 1.1, avg * 1.15]; // 5%, 10%, 15% increase
            }

            const futureMonths = ["Next Month", "2nd Month", "3rd Month"];
            const futureSales = predictFutureValues(salesValues);
            const futureExpenses = predictFutureValues(expenseValues);

            // Merge past and future data
            const forecastLabels = [...labels, ...futureMonths];
            const forecastSales = [...salesValues, ...futureSales];
            const forecastExpenses = [...expenseValues, ...futureExpenses];

            // Sales Forecast Chart
            new Chart(document.getElementById("salesForecastChart"), {
                type: "line",
                data: {
                    labels: forecastLabels,
                    datasets: [{
                        label: "Sales Forecast",
                        data: forecastSales,
                        borderColor: "blue",
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgb(75, 192, 192)',
                        fill: true,
                        tension: 0.3
                    }]
                }
            });

            // Expense Forecast Chart
            new Chart(document.getElementById("expenseForecastChart"), {
                type: "line",
                data: {
                    labels: forecastLabels,
                    datasets: [{
                        label: "Expense Forecast",
                        data: forecastExpenses,
                        borderColor: "red",
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgb(255, 99, 132)',
                        fill: true,
                        tension: 0.3
                    }]
                }
            });
        });

        // 10
        document.addEventListener("DOMContentLoaded", function () {
            // Parse JSON data from PHP
            const expenseData = JSON.parse('<?php echo $expensesJson; ?>');
            const thresholds = JSON.parse('<?php echo $thresholdsJson; ?>');
            const breachMonths = JSON.parse('<?php echo $breachMonthsJson; ?>');

            // Convert month numbers to month names
            const monthNames = [
                "January", "February", "March", "April", "May", "June",
                "July", "August", "September", "October", "November", "December"
            ];

            // Convert object to arrays for Chart.js
            const labels = Object.keys(expenseData).map(month => monthNames[month - 1]); // Convert month number to name
            const expenses = Object.values(expenseData);
            const thresholdValues = Object.keys(expenseData).map(month => thresholds[month] || 0);

            // Bar Chart for Expense Threshold
            new Chart(document.getElementById("expenseThresholdChart"), {
                type: "bar",
                data: {
                    labels: labels, // Now it shows Month Names
                    datasets: [{
                        label: "Monthly Expenses",
                        data: expenses,
                        backgroundColor: expenses.map((value, index) =>
                            value > thresholdValues[index] ? "red" : "blue"
                        )
                    },
                    {
                        label: "Dynamic Threshold (80%)",
                        data: thresholdValues,
                        type: "line",
                        borderColor: "orange",
                        borderWidth: 2,
                        fill: false
                    }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: "top"
                        }
                    }
                }
            });

            // Show threshold breach alerts
            const breachList = document.getElementById("breachList");
            if (breachMonths.length > 0) {
                breachMonths.forEach(month => {
                    let listItem = document.createElement("li");
                    listItem.className = "list-group-item list-group-item-danger";
                    listItem.textContent = `⚠️ High Expense in ${monthNames[month - 1]}`;
                    breachList.appendChild(listItem);
                });
            } else {
                let listItem = document.createElement("li");
                listItem.className = "list-group-item list-group-item-success";
                listItem.textContent = "✅ No threshold breaches!";
                breachList.appendChild(listItem);
            }
        });
    </script>
    <script>
        // Prepare data for all charts
        var chartData = <?php echo json_encode($processedData); ?>;
        var dailyData = <?php echo json_encode($dailyData); ?>;
        var monthlyData = <?php echo json_encode($monthlyData); ?>;
        var productData = <?php echo json_encode($productData); ?>;
        var demographicsData = <?php echo json_encode($demographicsData); ?>;
        var trendData = <?php echo json_encode($trendData); ?>;
    </script>
    <script src="../js/chart.js"></script>

    <script src="../js/sidebar.js"></script>
    <script src="../js/sort_items.js"></script>

</body>

</html>