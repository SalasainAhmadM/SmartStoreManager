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

// Fetch popular products based on selected month
$selectedMonth = isset($_GET['month']) ? intval($_GET['month']) : 0; // Default to "All Time"
$year = date('Y'); // Automatically select the current year

if ($selectedMonth == 0) {
    // If "All Time" is selected, do not filter by month
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
} else {
    // Filter by selected month
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
    AND MONTH(s.date) = ? AND YEAR(s.date) = ?
    GROUP BY p.name, b.name, p.type, p.price, p.description
    ORDER BY total_sales DESC
    LIMIT 10"; // Limit to top 10 products

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $owner_id, $selectedMonth, $year);
}

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
SELECT 
    CASE 
        WHEN e.category = 'business' THEN b.name
        WHEN e.category = 'branch' THEN br.location
    END AS category,
    SUM(e.amount) AS total_amount
FROM expenses e
LEFT JOIN business b ON e.category_id = b.id AND e.category = 'business'
LEFT JOIN branch br ON e.category_id = br.id AND e.category = 'branch'
WHERE e.owner_id = $owner_id
GROUP BY e.category, e.category_id
";
$categoryResult = $conn->query($categoryQuery);

$categoryData = [];
while ($row = $categoryResult->fetch_assoc()) {
    $categoryData[$row['category']] = '₱' . number_format($row['total_amount'], 2);
}

// Fetch Recurring vs One-Time Expenses by Month

$recurringQuery = "
SELECT 
    e.month,
    e.expense_type,
    SUM(e.amount) AS total_amount
FROM expenses e
WHERE e.owner_id = $owner_id
GROUP BY e.month, e.expense_type
";
$recurringResult = $conn->query($recurringQuery);

$recurringData = [];
while ($row = $recurringResult->fetch_assoc()) {
    $month = $row['month'];
    $expenseType = $row['expense_type'];
    $totalAmount = '₱' . number_format($row['total_amount'], 2);

    if (!isset($recurringData[$month])) {
        $recurringData[$month] = [
            'recurring' => [],
            'oneTime' => []
        ];
    }

    if ($expenseType === 'recurring') {
        $recurringData[$month]['recurring'][] = $totalAmount;
    } else {
        $recurringData[$month]['oneTime'][] = $totalAmount;
    }
}

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
$stockLevelsSold = [];

if ($business_id) {
    // Fetch Top Products Sold Across All Businesses
    $productQuery = "
    SELECT p.id, p.name, COALESCE(SUM(s.quantity), 0) AS total_sold
    FROM products p
    LEFT JOIN sales s ON p.id = s.product_id
    GROUP BY p.id, p.name
    ORDER BY total_sold DESC
    LIMIT 10"; // Show top 10 products sold across all businesses

    $productResult = $conn->query($productQuery);

    if ($productResult->num_rows > 0) {
        while ($row = $productResult->fetch_assoc()) {
            $products[] = $row['name'];
            $stockLevelsSold[] = $row['total_sold']; // Use actual sold quantity as stock level sold
        }
    }
}

// Prepare Data for JSON Output
$inventoryData = [
    'products' => $products,
    'stockLevelsSold' => $stockLevelsSold
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
    SELECT 
        month, 
        COALESCE(SUM(amount), 0) AS total_expenses
    FROM expenses
    WHERE owner_id = ?
    GROUP BY month
    ORDER BY month ASC";

$stmt = $conn->prepare($expenseQuery);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();

$expenseData = [];
$thresholds = [];
$breachMonths = [];
$positiveMonths = [];

// Initialize expense data for all months (1-12) with 0
for ($month = 1; $month <= 12; $month++) {
    $expenseData[$month] = 0;
}

// Fetch and process the result
while ($row = $result->fetch_assoc()) {
    $month = $row['month'];
    $totalExpense = $row['total_expenses'];

    // Set dynamic threshold (e.g., 80% of the total monthly expenses)
    $threshold = $totalExpense * 0.8;
    $thresholds[$month] = $threshold;

    $expenseData[$month] = $totalExpense;

    if ($totalExpense > $threshold) {
        $breachMonths[] = $month;
    } else {
        $positiveMonths[] = $month;
    }
}

// Convert data to JSON
$expensesJson = json_encode($expenseData);
$thresholdsJson = json_encode($thresholds);
$breachMonthsJson = json_encode($breachMonths);
$positiveMonthsJson = json_encode($positiveMonths);

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



function fetchFilteredData($owner_id, $selectedMonth)
{
    global $conn;

    // Validate input
    $selectedMonth = intval($selectedMonth);
    if ($selectedMonth < 1 || $selectedMonth > 12) {
        die(json_encode(['error' => 'Invalid month selected.']));
    }

    // Fetch sales and expenses for the selected month
    $year = date('Y'); // Current year
    $sql = "
        SELECT
            DATE(s.created_at) AS date,
            SUM(s.total_sales) AS daily_sales,
            SUM(e.amount) AS daily_expenses
        FROM sales s
        LEFT JOIN expenses e ON DATE(s.created_at) = DATE(e.created_at)
        WHERE s.owner_id = ?
        AND MONTH(s.created_at) = ?
        AND YEAR(s.created_at) = ?
        GROUP BY DATE(s.created_at)
        ORDER BY DATE(s.created_at) ASC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $owner_id, $selectedMonth, $year);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'date' => $row['date'],
            'sales' => floatval($row['daily_sales']),
            'expenses' => floatval($row['daily_expenses'])
        ];
    }

    return json_encode($data);
}

// Handle AJAX request
if (isset($_GET['action']) && $_GET['action'] === 'fetchFilteredData') {
    $owner_id = intval($_GET['owner_id']);
    $selectedMonth = intval($_GET['month']);
    echo fetchFilteredData($owner_id, $selectedMonth);
    exit;
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
                                            echo '<thead class="table-dark"><tr><th>Branches</th><th>Total Sales (₱)</th><th>Total Expenses (₱)</th><th>Include in Chart</th></tr></thead>';
                                            echo '<tbody>';

                                            // Loop through each branch of the business and display the expenses
                                            foreach ($branches as $branchLocation) {
                                                if ($branchLocation === 'No Branch for this Business') {
                                                    echo '<tr><td colspan="4" class="text-center">No Branch for this Business</td></tr>';
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
                                                    echo '<td><input type="checkbox" class="branch-checkbox" data-branch="' . $branchLocation . '" checked></td>';
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

                            <script>
                                document.addEventListener('DOMContentLoaded', function () {
                                    document.querySelectorAll('.branch-checkbox').forEach(checkbox => {
                                        checkbox.addEventListener('change', function () {
                                            const branchLocation = this.getAttribute('data-branch');
                                            if (!this.checked) {
                                                removeBranchFromChart(branchLocation);
                                            } else {
                                                addBranchToChart(branchLocation);
                                            }
                                        });
                                    });
                                });

                                function removeBranchFromChart(branchLocation) {
                                    if (financialChart) {
                                        const index = financialChart.data.labels.indexOf(branchLocation);
                                        if (index !== -1) {
                                            financialChart.data.labels.splice(index, 1);
                                            financialChart.data.datasets[0].data.splice(index, 1);
                                            financialChart.data.datasets[1].data.splice(index, 1);
                                            financialChart.update();
                                        }
                                    }
                                }

                                function addBranchToChart(branchLocation) {
                                    if (financialChart && selectedBusinessName) {
                                        const branchData = chartData[selectedBusinessName][branchLocation];
                                        if (branchData) {
                                            financialChart.data.labels.push(branchLocation);
                                            financialChart.data.datasets[0].data.push(branchData.sales);
                                            financialChart.data.datasets[1].data.push(branchData.expenses);
                                            financialChart.update();
                                        }
                                    }
                                }
                            </script>


                            <div class="col-md-7">
                                <h5 class="mt-5"><b>Financial Overview <i class="fas fa-info-circle"
                                            onclick="showInfo(' Financial Overview', 'This graph displays all the financial overview of your business/businesses.');"></i></b>
                                </h5>

                                <!-- Original Chart -->
                                <div class="chart-container mb-4">
                                    <canvas id="financialChart"></canvas>
                                    <button class="btn btn-dark mt-2 mb-5" id="printChartButton">
                                        <i class="fas fa-print me-2"></i> Generate Report
                                    </button>
                                </div>

                                <!-- Sales vs Expenses Chart -->
                                <div class="chart-container mb-4">
                                    <h6>Sales vs Expenses <i class="fas fa-info-circle"
                                            onclick="showInfo(' Sales vs Expenses', 'This graph displays all the sales vs expenses.');"></i>
                                    </h6>
                                    <canvas id="salesExpensesChart"></canvas>
                                </div>

                                <div class="mt-3">
                                    <label for="monthFilter"><b>Filter Sales vs Expenses by Month
                                            (<?php echo date("Y"); ?>):</b></label>
                                    <select id="monthFilter" class="form-control"
                                        onchange="filterSalesExpensesByMonth(this.value)">
                                        <option value="0">Select Month</option>
                                        <option value="1">January</option>
                                        <option value="2">February</option>
                                        <option value="3">March</option>
                                        <option value="4">April</option>
                                        <option value="5">May</option>
                                        <option value="6">June</option>
                                        <option value="7">July</option>
                                        <option value="8">August</option>
                                        <option value="9">September</option>
                                        <option value="10">October</option>
                                        <option value="11">November</option>
                                        <option value="12">December</option>
                                    </select>
                                </div>

                                <button class="btn btn-dark mt-2 mb-5"
                                    onclick="printFinancialOverviewAndSalesvsExpensesTable()">
                                    <i class="fas fa-print me-2"></i> Generate Report
                                </button>



                                <!-- Profit Margin Chart -->
                                <div class="chart-container mb-4">
                                    <h6>Profit Margin Trends <i class="fas fa-info-circle"
                                            onclick="showInfo(' Profit Margin Trends', 'This graph displays all the profit margin trends.');"></i>
                                    </h6>
                                    <canvas id="profitMarginChart"></canvas>
                                </div>

                                <!-- Cash Flow Chart -->
                                <div class="chart-container mb-4">
                                    <h6>Monthly Cash Flow <i class="fas fa-info-circle"
                                            onclick="showInfo(' Monthly Cash Flow', 'This graph displays all the cash inflow and cash outflow.');"></i>
                                    </h6>
                                    <canvas id="cashFlowChart"></canvas>
                                </div>

                                <!-- 6 -->

                            </div>


                        </div>


                        <div class="col-md-12 mt-5">
                            <h1><b><i class="fa-solid fa-chart-line"></i> Business Comparison <i
                                        class="fas fa-info-circle"
                                        onclick="showInfo(' Business Comparison', 'This graph displays all the comparison of all your businesses with sales and expenses.');"></i></i>
                            </h1>
                            <div class="row">
                                <!-- Business Performance Chart -->
                                <div class="col-md-6">
                                    <div class="chart-container mb-4" style="height: 400px;">
                                        <h5 class="mt-5"><b>Business Performance Comparison </b></h5>
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
                            <h1><b><i class="fa-solid fa-chart-pie"></i> Expense Breakdown <i class="fas fa-info-circle"
                                        onclick="showInfo(' Expense Breakdown', 'This graph displays all the category-wise expenses and recurring vs. one-time expenses.');"></i>
                                </b></h1>
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
                            <h1>
                                <b>
                                    <i class="fa-solid fa-boxes-stacked"></i> Inventory Product Sold
                                    <i class="fas fa-info-circle"
                                        onclick="showInfo('Inventory Product Sold', 'This graph displays the stock levels sold of top products across all businesses.');"></i>
                                </b>
                            </h1>
                            <div class="row">
                                <!-- Stock Levels Sold Chart -->
                                <div class="col-md-12">
                                    <div class="chart-container mb-4" style="height: 400px;">
                                        <h5 class="mt-5"><b>Stock Levels Sold of Top Products Across All Businesses</b>
                                        </h5>
                                        <canvas id="stockLevelSoldChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 mt-5">
                            <h1><b><i class="fa-solid fa-chart-line"></i> Key Performance Indicators (KPIs) <i
                                        class="fas fa-info-circle"
                                        onclick="showInfo(' Key Performance Indicators', 'This chart displays all the gross profit, revenue growth rate and return of investment.');"></i></b>
                            </h1>
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
                            <h1><b><i class="fa-solid fa-chart-line"></i> Forecasting & Predictions <i
                                        class="fas fa-info-circle"
                                        onclick="showInfo(' Forecasting & Predictions', 'This graph displays all the sales forecast and expense forecast.');"></i></b>
                            </h1>
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
                            <h1><b><i class="fa-solid fa-box"></i> Product/Service Analysis <i
                                        class="fas fa-info-circle"
                                        onclick="showInfo(' Product/Service Analysis', 'This graph displays all the top-selling products/services and low-performing products/serices.');"></i></b>
                            </h1>
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
                                        <h5 class="mt-5 mb-3"><b>Product/Service Profitability Analysis<i
                                                    class="fas fa-info-circle"
                                                    onclick="showInfo(' Product/Service Profitability Analysis', 'This graph displays all the products/services profitability analysis.');"></i></b>
                                        </h5>
                                        <canvas id="productProfitabilityChart"></canvas>
                                    </div>
                                </div>

                                <!-- Customer Demographics -->
                                <div class="col-md-12 mt-5">
                                    <h1><b><i class="fa-solid fa-users"></i> Customer Demographics <i
                                                class="fas fa-info-circle"
                                                onclick="showInfo(' Customer Demographics', 'This graph displays all the top products by location.');"></i></b>
                                    </h1>
                                    <button id="selectBusinessBtn" class="btn btn-primary mb-1 mt-2">
                                        <i class="fa-solid fa-filter"></i> Select Business and Branches
                                    </button>
                                    <div class="chart-container mb-4" style="height: 400px;">
                                        <h5 class="mt-5"><b>Top Products by Location</b></h5>
                                        <canvas id="demographicsChart"></canvas>
                                    </div>
                                </div>

                                <!-- Trend Analysis -->
                                <div class="col-md-12 mt-5">
                                    <h1><b><i class="fa-solid fa-chart-line"></i> Trend Analysis<i
                                                class="fas fa-info-circle"
                                                onclick="showInfo(' Trend Analysis', 'This graph displays all the seasonal trends and growth rate analysis.');"></i></b>
                                    </h1>
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
                                <h1><b><i class="fa-solid fa-boxes icon"></i> Popular Products<i
                                            class="fas fa-info-circle"
                                            onclick="showInfo(' Popular Products', 'This graph displays all the popular products and can be filtered also by months.');"></i></b>
                                </h1>
                                <div class="col-md-12 dashboard-content">
                                    <div class="mb-3">
                                        <label for="monthFilter"><b>Filter by Month
                                                (<?php echo date("Y"); ?>):</b></label>
                                        <select id="monthFilter" class="form-control"
                                            onchange="filterProductsByMonth(this.value)">
                                            <option value="0">All Time</option>
                                            <option value="1">January</option>
                                            <option value="2">February</option>
                                            <option value="3">March</option>
                                            <option value="4">April</option>
                                            <option value="5">May</option>
                                            <option value="6">June</option>
                                            <option value="7">July</option>
                                            <option value="8">August</option>
                                            <option value="9">September</option>
                                            <option value="10">October</option>
                                            <option value="11">November</option>
                                            <option value="12">December</option>
                                        </select>
                                    </div>
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
                                                        <td><?php echo '₱' . number_format($product['total_sales'], 2); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="6" style="text-align: center;">No Popular Products Found
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                    <button class="btn btn-primary mt-2 mb-5" onclick="printPopularProducts()">
                                        <i class="fas fa-print me-2"></i> Generate Report
                                    </button>
                                </div>
                            </div>
                        </div>




                        <!--  -->
                        <div class="col-md-12 mt-5">
                            <h1>
                                <b>
                                    <i class="fa-solid fa-exclamation-triangle"></i> Alerts & Thresholds
                                    <i class="fas fa-info-circle"
                                        onclick="showInfo('Alerts & Thresholds', 'This graph displays expense threshold breaches and positive responses based on past month\'s expenses.');"></i>
                                </b>
                            </h1>
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
                                        <h5><b>⚠️ Threshold Alerts</b></h5>
                                        <ul id="breachList" class="list-group"></ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="recentActivitiesSection">
                            <div class="col-md-12 mt-5">
                                <h1><b><i class="fas fa-bell"></i> Activity Log <i class="fas fa-info-circle"
                                            onclick="showInfo(' Activity Log', 'This table displays all the latest activities.');"></i></b>
                                </h1>
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
            <input type="text" id="business-location" class="form-control mb-2" placeholder="location">
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
                    const location = document.getElementById('business-location').value.trim();

                    if (!businessName || !businessAsset || !employeeCount) {
                        Swal.fire('Error', 'Please fill in all required fields.', 'error');
                        return;
                    }

                    const formData = new FormData();
                    formData.append('name', businessName);
                    formData.append('description', businessDescription);
                    formData.append('asset', businessAsset);
                    formData.append('employeeCount', employeeCount);
                    formData.append('location', location);
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
        var recurringValues = months.map(m => expenseData.recurringByMonth[m].recurring.reduce((sum, val) => sum + parseFloat(val.replace('₱', '').replace(',', '')), 0));
        var oneTimeValues = months.map(m => expenseData.recurringByMonth[m].oneTime.reduce((sum, val) => sum + parseFloat(val.replace('₱', '').replace(',', '')), 0));

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

        // Bar Chart: Stock Levels Sold
        var ctx1 = document.getElementById('stockLevelSoldChart').getContext('2d');
        new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: inventoryData.products,
                datasets: [{
                    label: 'Stock Levels Sold',
                    data: inventoryData.stockLevelsSold,
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
            const positiveMonths = JSON.parse('<?php echo $positiveMonthsJson; ?>');

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
                    }]
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

            // Show threshold breach alerts or positive response
            const breachList = document.getElementById("breachList");
            if (breachMonths.length > 0) {
                breachMonths.forEach(month => {
                    let listItem = document.createElement("li");
                    listItem.className = "list-group-item list-group-item-danger";
                    listItem.textContent = `⚠️ High Expense in ${monthNames[month - 1]}`;
                    breachList.appendChild(listItem);
                });
            } else if (positiveMonths.length > 0) {
                positiveMonths.forEach(month => {
                    let listItem = document.createElement("li");
                    listItem.className = "list-group-item list-group-item-success";
                    listItem.textContent = `✅ Expenses within limit in ${monthNames[month - 1]}`;
                    breachList.appendChild(listItem);
                });
            } else {
                let listItem = document.createElement("li");
                listItem.className = "list-group-item list-group-item-warning";
                listItem.textContent = "⚠️ No data available for the past month.";
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
    <script src="../js/show_info.js"></script>





    <script>
        function filterProductsByMonth(selectedMonth) {
            // Get the owner ID from the session or URL
            const ownerId = <?php echo json_encode($owner_id); ?>;

            // Send an AJAX request to fetch filtered products
            fetch(`../endpoints/filter_products.php?owner_id=${ownerId}&month=${selectedMonth}`)
                .then(response => response.json())
                .then(data => {
                    // Clear the existing table rows
                    const tableBody = document.querySelector('#product-table tbody');
                    tableBody.innerHTML = '';

                    // Populate the table with the filtered data
                    if (data.length > 0) {
                        data.forEach(product => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                            <td>${product.product_name}</td>
                            <td>${product.business_name}</td>
                            <td>${product.type}</td>
                            <td>₱${parseFloat(product.price).toFixed(2)}</td>
                            <td>${product.description}</td>
                            <td>₱${parseFloat(product.total_sales).toFixed(2)}</td>
                        `;
                            tableBody.appendChild(row);
                        });
                    } else {
                        // If no products are found, display a message
                        const row = document.createElement('tr');
                        row.innerHTML = `
                        <td colspan="6" style="text-align: center;">No Popular Products Found</td>
                    `;
                        tableBody.appendChild(row);
                    }
                })
                .catch(error => {
                    console.error('Error fetching filtered products:', error);
                });
        }
    </script>

    <script>
        function printPopularProducts() {
            const table = document.getElementById('product-table');

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
                <h1>Popular Products Report</h1>
                ${table.outerHTML}               
            </body>
            </html>
            `);
            printWindow.print();
            printWindow.document.close();
        }
    </script>
</body>

</html>