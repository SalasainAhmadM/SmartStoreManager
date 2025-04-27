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
                    // Trigger modal if needed
                    " . ($isNewOwner ? "triggerAddBusinessModal();" : "") . "
                    
                    // Remove 'status' and 'id' from the URL
                    const url = new URL(window.location.href);
                    url.searchParams.delete('status');
                    url.searchParams.delete('id');
                    window.history.replaceState({}, document.title, url.pathname);
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
    UNION
    SELECT
        DATE(e.created_at) as date,
        b.name as business_name,
        SUM(e.amount) as daily_expenses
    FROM expenses e
    JOIN branch br ON e.category = 'branch' AND e.category_id = br.id
    JOIN business b ON br.business_id = b.id
    WHERE e.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    AND b.owner_id = ?
    GROUP BY DATE(e.created_at), b.name
) e ON dates.date = e.date AND s.business_name = e.business_name
JOIN business b ON COALESCE(s.business_name, e.business_name) = b.name
WHERE b.owner_id = ?
ORDER BY dates.date";

$stmtDaily = $conn->prepare($sqlDaily);
$stmtDaily->bind_param("iiii", $owner_id, $owner_id, $owner_id, $owner_id);
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

$selectedBusiness = $_GET['business'] ?? 'all';
$selectedBranch = $_GET['branch'] ?? 'all';

$filterCondition = "";
if ($selectedBranch !== 'all') {
    // Specific branch selected
    $filterCondition = " AND e.category = 'branch' AND e.category_id = " . intval($selectedBranch);
} elseif ($selectedBusiness !== 'all') {
    // Business selected: include business AND all its branches
    $branchIds = [];

    // Get all branch IDs under this business
    $branchQuery = "SELECT id FROM branch WHERE business_id = " . intval($selectedBusiness);
    $branchResult = $conn->query($branchQuery);
    while ($row = $branchResult->fetch_assoc()) {
        $branchIds[] = $row['id'];
    }

    $branchIdsList = implode(',', $branchIds);

    $filterCondition = " AND (
        (e.category = 'business' AND e.category_id = " . intval($selectedBusiness) . ")
        " . (!empty($branchIds) ? "OR (e.category = 'branch' AND e.category_id IN ($branchIdsList))" : "") . "
    )";
}


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
$filterCondition
GROUP BY e.category, e.category_id
";

$categoryResult = $conn->query($categoryQuery);

$categoryData = [];
while ($row = $categoryResult->fetch_assoc()) {
    $categoryData[$row['category']] = '₱' . number_format($row['total_amount'], 2);
}


// Fetch Recurring vs One-Time Expenses by Month, adjusted to business/branch
$recurringQuery = "
SELECT 
    e.month,
    e.expense_type,
    CASE 
        WHEN e.category = 'business' THEN b.name
        WHEN e.category = 'branch' THEN br.location
        ELSE 'Other'
    END AS category_name,
    SUM(e.amount) AS total_amount
FROM expenses e
LEFT JOIN business b ON e.category_id = b.id AND e.category = 'business'
LEFT JOIN branch br ON e.category_id = br.id AND e.category = 'branch'
WHERE e.owner_id = $owner_id
$filterCondition
GROUP BY e.month, e.expense_type, e.category, e.category_id
";

$recurringResult = $conn->query($recurringQuery);

$recurringData = [];
while ($row = $recurringResult->fetch_assoc()) {
    $month = $row['month'];
    $expenseType = $row['expense_type'];
    $categoryName = $row['category_name'];
    $totalAmount = '₱' . number_format($row['total_amount'], 2);

    if (!isset($recurringData[$month])) {
        $recurringData[$month] = [];
    }
    if (!isset($recurringData[$month][$categoryName])) {
        $recurringData[$month][$categoryName] = [
            'recurring' => [],
            'oneTime' => []
        ];
    }

    if ($expenseType === 'recurring') {
        $recurringData[$month][$categoryName]['recurring'][] = $totalAmount;
    } else {
        $recurringData[$month][$categoryName]['oneTime'][] = $totalAmount;
    }
}

// Final output
$expenseDataBreakdown = [
    'categories' => $categoryData,
    'recurringByMonth' => $recurringData
];

// 7
// Get the business ID of the logged-in owner
$businesses = [];
$businessQuery = "SELECT id, name FROM business WHERE owner_id = $owner_id";
$businessResult = $conn->query($businessQuery);
while ($row = $businessResult->fetch_assoc()) {
    $businesses[] = $row;
}

// Get selected parameters
$selected_business_id = isset($_GET['business']) && $_GET['business'] !== 'all' ? intval($_GET['business']) : null;
$selected_branch_id = isset($_GET['branch']) && $_GET['branch'] !== 'all' ? intval($_GET['branch']) : null;

// Validate business-branch relationship
if ($selected_business_id !== null && $selected_branch_id !== null && $selected_branch_id != 0) {
    $branchCheckQuery = "SELECT id FROM branch WHERE id = $selected_branch_id AND business_id = $selected_business_id";
    $branchCheckResult = $conn->query($branchCheckQuery);
    if ($branchCheckResult->num_rows === 0) {
        $selected_branch_id = null;
    }
}


// Get branches for selected business
$branches = [];
if ($selected_business_id) {
    $branchQuery = "SELECT id, location FROM branch WHERE business_id = $selected_business_id";
    $branchResult = $conn->query($branchQuery);
    while ($row = $branchResult->fetch_assoc()) {
        $branches[] = $row;
    }
}

$whereClauses = ["p.business_id IN (SELECT id FROM business WHERE owner_id = $owner_id)"];

// Filter by specific business if selected
if ($selected_business_id) {
    $whereClauses[] = "p.business_id = " . intval($selected_business_id);
}

// Filter based on branch or business level
if ($selected_branch_id !== null) {
    if ($selected_branch_id == 0) {
        // Business-level only sales
        $whereClauses[] = "s.type = 'business'";
    } else {
        // Branch-level sales
        $whereClauses[] = "s.branch_id = " . intval($selected_branch_id);
        $whereClauses[] = "s.type = 'branch'";
    }
} else {
    // If no branch selected, include both business and branch-level sales related to the owner
    $whereClauses[] = "(s.type = 'business' OR s.type = 'branch')";
}

// Fetch product sales data
$products = [];
$stockLevelsSold = [];
$colors = [];

$productQuery = "
    SELECT p.id, p.name, COALESCE(SUM(s.quantity), 0) AS total_sold
    FROM products p
    LEFT JOIN sales s ON s.product_id = p.id
    WHERE " . implode(" AND ", $whereClauses) . "
    GROUP BY p.id, p.name
    ORDER BY total_sold DESC
    LIMIT 10";

$productResult = $conn->query($productQuery);


if ($productResult->num_rows > 0) {
    $colorIndex = 0;
    $colorPalette = [
        '#FF6384',
        '#36A2EB',
        '#FFCE56',
        '#4BC0C0',
        '#9966FF',
        '#FF9F40',
        '#EB3B5A',
        '#00B894',
        '#FD7272',
        '#1B1464'
    ];

    while ($row = $productResult->fetch_assoc()) {
        $products[] = $row['name'];
        $stockLevelsSold[] = $row['total_sold'];
        $colors[] = $colorPalette[$colorIndex % count($colorPalette)];
        $colorIndex++;
    }
}

// Prepare data for JSON
$inventoryData = [
    'products' => $products,
    'stockLevelsSold' => $stockLevelsSold,
    'colors' => $colors
];
// 8
// Fetch Total Sales
$selectedBusiness = $_GET['business'] ?? 'all';
$selectedBranch = $_GET['branch'] ?? 'all';
$selected_business_id = $selectedBusiness !== 'all' ? intval($selectedBusiness) : null;
$selected_branch_id = $selectedBranch !== 'all' ? intval($selectedBranch) : null;

$businessFilterSQL = "";
$expenseFilterSQL = "";

if ($selectedBusiness !== 'all' && $selectedBranch === 'all') {
    // Only filter by Business
    $businessFilterSQL = "AND p.business_id = $selectedBusiness";
    $expenseFilterSQL = "AND (
        (e.category = 'business' AND e.category_id = $selectedBusiness)
    )";
} elseif ($selectedBusiness !== 'all' && $selectedBranch !== 'all') {
    // Filter by both Business and Branch
    $businessFilterSQL = "AND p.business_id = $selectedBusiness AND s.branch_id = $selectedBranch";
    $expenseFilterSQL = "AND (
        (e.category = 'branch' AND e.category_id = $selectedBranch)
    )";
}

// Fetch Total Sales
$salesQuery = "
SELECT SUM(s.total_sales) AS total_sales
FROM sales s
JOIN products p ON s.product_id = p.id
WHERE p.business_id IN (SELECT id FROM business WHERE owner_id = $owner_id)
$businessFilterSQL
";
$salesResult = $conn->query($salesQuery);
$salesData = $salesResult->fetch_assoc();
$totalSales = $salesData['total_sales'] ?? 0;

// Fetch Total Expenses
$expensesQuery = "
SELECT SUM(e.amount) AS total_expenses
FROM expenses e
WHERE e.owner_id = $owner_id
$expenseFilterSQL
";
$expensesResult = $conn->query($expensesQuery);
$expensesData = $expensesResult->fetch_assoc();
$totalExpenses = $expensesData['total_expenses'] ?? 0;

// Revenue Growth (Current vs Previous Month)
$revenueGrowthQuery = "
SELECT
SUM(CASE WHEN MONTH(s.date) = MONTH(CURRENT_DATE()) THEN s.total_sales ELSE 0 END) AS currentMonthSales,
SUM(CASE WHEN MONTH(s.date) = MONTH(CURRENT_DATE()) - 1 THEN s.total_sales ELSE 0 END) AS previousMonthSales
FROM sales s
JOIN products p ON s.product_id = p.id
WHERE p.business_id IN (SELECT id FROM business WHERE owner_id = $owner_id)
$businessFilterSQL
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
$filters = [];
$params = [];
$types = "";

// Base query for sales
$salesForecastQuery = "
SELECT DATE_FORMAT(s.date, '%Y-%m') AS month, SUM(s.total_sales) AS total_sales
FROM sales s
JOIN products p ON s.product_id = p.id
WHERE p.business_id IN (
    SELECT id FROM business WHERE owner_id = ?
)";
$params[] = $owner_id;
$types .= "i";

// Add filters if business/branch selected
if ($selectedBusiness !== 'all') {
    $salesForecastQuery .= " AND p.business_id = ?";
    $params[] = $selectedBusiness;
    $types .= "i";

    if ($selectedBranch !== 'all') {
        if ($selectedBranch == "0") {
            // Business-level filter
            $salesForecastQuery .= " AND s.branch_id = 0 AND s.type = 'business'";
        } else {
            // Branch-level filter
            $salesForecastQuery .= " AND s.branch_id = ? AND s.type = 'branch'";
            $params[] = $selectedBranch;
            $types .= "i";
        }
    }
}

$salesForecastQuery .= " GROUP BY month ORDER BY month ASC";

$stmt = $conn->prepare($salesForecastQuery);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$salesData = [];
while ($row = $result->fetch_assoc()) {
    $salesData[$row['month']] = $row['total_sales'];
}
$expensesForecastQuery = "
SELECT DATE_FORMAT(e.created_at, '%Y-%m') AS month, SUM(e.amount) AS total_expenses
FROM expenses e
WHERE e.owner_id = ?
";
$params = [$owner_id];
$types = "i";

// Filter by selected business or branch
if ($selectedBusiness !== 'all') {
    $expensesForecastQuery .= " AND e.category = 'business' AND e.category_id = ?";
    $params[] = $selectedBusiness;
    $types .= "i";

    if ($selectedBranch !== 'all') {
        if ($selectedBranch == "0") {
            // Business-level
            $expensesForecastQuery .= " AND e.category = 'business'";
        } else {
            // Branch-level
            $expensesForecastQuery .= " AND e.category = 'branch' AND e.category_id = ?";
            $params[] = $selectedBranch;
            $types .= "i";
        }
    }
}

$expensesForecastQuery .= " GROUP BY month ORDER BY month ASC";

$stmt = $conn->prepare($expensesForecastQuery);
$stmt->bind_param($types, ...$params);
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
// 10
$selectedBusiness = $_GET['business'] ?? 'all';
$selectedBranch = $_GET['branch'] ?? 'all';

// Fetch total assets of the business
$assetQuery = "
    SELECT 
        SUM(CAST(asset AS DECIMAL(10, 2))) AS total_assets
    FROM business
    WHERE owner_id = ?
";

$stmt = $conn->prepare($assetQuery);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$assetResult = $stmt->get_result();
$totalAssets = $assetResult->fetch_assoc()['total_assets'] ?? 0; // Default to 0 if no assets found

// Build dynamic expenses query
$expenseQuery = "
    SELECT 
        month, 
        COALESCE(SUM(CAST(amount AS DECIMAL(10,2))), 0) AS total_expenses
    FROM expenses
    WHERE owner_id = ?
";

$params = [$owner_id];
$types = "i";

if ($selectedBusiness !== 'all' && $selectedBranch === 'all') {
    // Filter expenses for business and all its branches
    $expenseQuery .= " AND (
        (category = 'business' AND category_id = ?)
        OR 
        (category = 'branch' AND category_id IN (
            SELECT id FROM branch WHERE business_id = ?
        ))
    )";
    $params[] = $selectedBusiness;
    $params[] = $selectedBusiness;
    $types .= "ii"; // two integers
} elseif ($selectedBranch !== 'all') {
    // Filter only by branch
    $expenseQuery .= " AND category = 'branch' AND category_id = ?";
    $params[] = $selectedBranch;
    $types .= "i";
}
// else: no filter, show all expenses

$expenseQuery .= " GROUP BY month ORDER BY month ASC";

// Prepare and bind
$stmt = $conn->prepare($expenseQuery);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Initialize
$expenseData = [];
$thresholds = [];
$breachMonths = [];
$positiveMonths = [];

// Initialize all months to 0
for ($month = 1; $month <= 12; $month++) {
    $expenseData[$month] = 0;
}

// Fetch and process
$previousMonthExpense = null;

while ($row = $result->fetch_assoc()) {
    $month = $row['month'];
    $totalExpense = $row['total_expenses'];

    $expenseData[$month] = $totalExpense;

    $thresholds[$month] = $totalExpense * 0.8;

    if ($totalExpense > $totalAssets) {
        $breachMonths[] = $month;
    } else {
        $positiveMonths[] = $month;
    }

    if ($previousMonthExpense !== null && $totalExpense < $previousMonthExpense) {
        $positiveMonths[] = $month;
    }

    $previousMonthExpense = $totalExpense;
}

// Convert data to JSON
$expensesJson = json_encode($expenseData);
$thresholdsJson = json_encode($thresholds);
$breachMonthsJson = json_encode($breachMonths);
$positiveMonthsJson = json_encode($positiveMonths);


// Get customer demographics data

$sqlDemographics = "SELECT
  CASE 
    WHEN s.branch_id = 0 THEN CONCAT(b.location, ' - Main Branch')
    ELSE br.location
  END AS location,
  p.name AS product_name,
  COUNT(*) AS purchase_count,
  SUM(s.total_sales) AS total_revenue
FROM sales s
JOIN products p ON s.product_id = p.id
JOIN business b ON p.business_id = b.id
LEFT JOIN branch br ON s.branch_id = br.id
WHERE b.owner_id = ?
AND s.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY location, p.name
ORDER BY total_revenue DESC
LIMIT 10
";

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
<style>
    .dashboard-body {
        position: fixed;
    }

    .chart-container {
        position: relative;
        height: 430px;
        padding: 15px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        margin-bottom: 2rem;
    }

    .btn-print {
        background: #2c3e50;
        color: white;
        padding: 8px 15px;
        margin-top: 15px;
    }

    /* Mobile styles */
    @media (max-width: 768px) {
        .dashboard-content h1 {
            font-size: 20px;
            margin-bottom: 1.5rem;
        }

        .chart-container {
            height: 300px;
            margin-bottom: 1.5rem;
        }

        .col-md-6 {
            flex: 0 0 100%;
            max-width: 100%;
        }

        .form-control {
            font-size: 14px;
        }
    }

    /* Small mobile styles */
    @media (max-width: 480px) {
        .dashboard-content h1 {
            font-size: 18px;
        }

        .chart-container {
            height: 250px;
            padding: 10px;
        }

        h5 {
            font-size: 16px !important;
        }
    }

    canvas {
        max-width: 100%;
        height: auto !important;
    }

    #popularProductsSection .dashboard-content,
    #recentActivitiesSection .dashboard-content {
        overflow-x: auto;
    }

    .table {
        min-width: 600px;
    }

    #product-table th,
    #product-table td {
        white-space: nowrap;
    }

    @media (max-width: 768px) {

        #popularProductsSection h1,
        #recentActivitiesSection h1 {
            font-size: 1.5rem;
        }

        .table {
            font-size: 14px;
        }

        .table th,
        .table td {
            padding: 0.75rem 0.5rem;
        }

        .form-control {
            font-size: 14px;
        }

        .btn-primary {
            width: 100%;
            padding: 0.5rem;
            font-size: 14px;
        }

        .fa-info-circle {
            font-size: 1rem;
        }
    }

    @media (max-width: 480px) {

        #popularProductsSection h1,
        #recentActivitiesSection h1 {
            font-size: 1.3rem;
        }

        .table {
            font-size: 12px;
        }

        .table th button {
            padding: 0.25rem;
        }

        .btn-primary {
            font-size: 12px;
        }

        td:nth-child(3) {
            min-width: 80px;
        }
    }

    .btn-primary {
        margin-top: 1rem;
    }

    .fa-info-circle {
        margin-left: 0.5rem;
        cursor: pointer;
    }

    .table td:nth-child(3) {
        font-weight: bold;
    }
</style>

<body class="d-flex">

    <div id="particles-js"></div>

    <?php include '../components/owner_sidebar.php'; ?>
    <style>

    </style>
    <div class="container-fluid page-body">
        <div class="row">
            <div class="col-12 dashboard-body">
                <div class="dashboard-content">
                    <h1><b><i class="fas fa-tachometer-alt me-2"></i> Dashboard Overview</b></h1>

                    <div class="container-fluid">

                        <div class="col-md-12 mt-2" style="display: none;">
                            <div class="row">
                                <!-- Financial Overview Chart -->
                                <div class="col-md-6 mb-4">
                                    <div class="chart-container mb-4">
                                        <div class="row">
                                            <div class="col-md-6 mb-1">
                                                <label for="financialBusinessSelect"><b>Select Business:</b></label>
                                                <select id="financialBusinessSelect" class="form-control"
                                                    onchange="handleBusinessSelect(this, 'financialBranchSelect', 'financialBranchSelectContainer'); updateFinancialChart(this.value, 'all')">
                                                    <option value="all">All Businesses</option>
                                                    <?php foreach (array_keys($businessData) as $businessName): ?>
                                                        <option value="<?= htmlspecialchars($businessName) ?>">
                                                            <?= htmlspecialchars($businessName) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-1" id="financialBranchSelectContainer">
                                                <label for="financialBranchSelect"><b>Select Branch:</b></label>
                                                <select id="financialBranchSelect" class="form-control"
                                                    onchange="updateFinancialChart(document.getElementById('financialBusinessSelect').value, this.value)">
                                                    <option value="all">All Branches</option>
                                                </select>
                                            </div>
                                        </div>
                                        <h5 class="mt-2"><b><i class="fa-solid fa-chart-line"></i> Financial
                                                Overview</b></h5>
                                        <canvas id="financialChart"></canvas>
                                    </div>
                                </div>

                                <!-- Sales vs Expenses Chart -->
                                <div class="col-md-6 mb-4">
                                    <div class="chart-container mb-4">
                                        <div class="row">
                                            <div class="col-md-6 mb-1">
                                                <label for="salesExpensesBusinessSelect"><b>Select Business:</b></label>
                                                <select id="salesExpensesBusinessSelect" class="form-control"
                                                    onchange="handleBusinessSelect(this, 'salesExpensesBranchSelect', 'salesExpensesBranchSelectContainer'); updateSalesExpensesChart(this.value, 'all')">
                                                    <option value="all">All Businesses</option>
                                                    <?php foreach (array_keys($businessData) as $businessName): ?>
                                                        <option value="<?= htmlspecialchars($businessName) ?>">
                                                            <?= htmlspecialchars($businessName) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-1" id="salesExpensesBranchSelectContainer">
                                                <label for="salesExpensesBranchSelect"><b>Select Branch:</b></label>
                                                <select id="salesExpensesBranchSelect" class="form-control"
                                                    onchange="updateSalesExpensesChart(document.getElementById('salesExpensesBusinessSelect').value, this.value)">
                                                    <option value="all">All Branches</option>
                                                </select>
                                            </div>
                                        </div>
                                        <h5 class="mt-2"><b>Sales vs Expenses</b></h5>
                                        <canvas id="salesExpensesChart"></canvas>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Profit Margin Chart -->
                                <div class="col-md-6">
                                    <div class="chart-container mb-4">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="profitMarginBusinessSelect"><b>Select Business:</b></label>
                                                <select id="profitMarginBusinessSelect" class="form-control"
                                                    onchange="handleBusinessSelect(this, 'profitMarginBranchSelect', 'profitMarginBranchSelectContainer'); updateProfitMarginChart(this.value, 'all')">
                                                    <option value="all">All Businesses</option>
                                                    <?php foreach (array_keys($businessData) as $businessName): ?>
                                                        <option value="<?= htmlspecialchars($businessName) ?>">
                                                            <?= htmlspecialchars($businessName) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3" id="profitMarginBranchSelectContainer">
                                                <label for="profitMarginBranchSelect"><b>Select Branch:</b></label>
                                                <select id="profitMarginBranchSelect" class="form-control"
                                                    onchange="updateProfitMarginChart(document.getElementById('profitMarginBusinessSelect').value, this.value)">
                                                    <option value="all">All Branches</option>
                                                </select>
                                            </div>
                                        </div>
                                        <h5 class="mt-2"><b>Profit Margin Trends</b></h5>
                                        <canvas id="profitMarginChart"></canvas>
                                    </div>
                                </div>

                                <!-- Cash Flow Chart -->
                                <div class="col-md-6">
                                    <div class="chart-container mb-4">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="cashFlowBusinessSelect"><b>Select Business:</b></label>
                                                <select id="cashFlowBusinessSelect" class="form-control"
                                                    onchange="handleBusinessSelect(this, 'cashFlowBranchSelect', 'cashFlowBranchSelectContainer'); updateCashFlowChart(this.value, 'all')">
                                                    <option value="all">All Businesses</option>
                                                    <?php foreach (array_keys($businessData) as $businessName): ?>
                                                        <option value="<?= htmlspecialchars($businessName) ?>">
                                                            <?= htmlspecialchars($businessName) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3" id="cashFlowBranchSelectContainer">
                                                <label for="cashFlowBranchSelect"><b>Select Branch:</b></label>
                                                <select id="cashFlowBranchSelect" class="form-control"
                                                    onchange="updateCashFlowChart(document.getElementById('cashFlowBusinessSelect').value, this.value)">
                                                    <option value="all">All Branches</option>
                                                </select>
                                            </div>
                                            <!-- Financial Overview Chart -->
                                            <div style="display: none;" class="col-md-6 mb-4">
                                                <div class="chart-container mb-4">

                                                    <h5 class="mt-2"><b><i class="fa-solid fa-chart-line"></i> Financial
                                                            Overview</b></h5>
                                                    <canvas id="financialOverviewNewChart"></canvas>
                                                </div>
                                            </div>

                                        </div>
                                        <h5 class="mt-2"><b>Monthly Cash Flow</b></h5>
                                        <canvas id="cashFlowChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- // 1 -->
                        <?php
                        $businessNewData = [];
                        $branchNewInfo = []; // Stores branch details by ID
                        
                        while ($business = $businessResult->fetch_assoc()) {
                            $businessId = $business['id'];
                            $businessNewData[$business['name']] = [];

                            $branchQuery = "SELECT id, location FROM branch WHERE business_id = ?";
                            $branchStmt = $conn->prepare($branchQuery);
                            $branchStmt->bind_param("i", $businessId);
                            $branchStmt->execute();
                            $branchResult = $branchStmt->get_result();

                            while ($branch = $branchResult->fetch_assoc()) {
                                $branchId = $branch['id'];
                                $businessNewData[$business['name']][$branchId] = $branch['location'];
                                $branchNewInfo[$branchId] = $branch['location'];
                            }
                        }

                        ?>


                        <?php
                        $businessNewData = [];
                        $branchNewData = [];
                        $businessIdNameMap = [];

                        $businessQuery = "SELECT id, name FROM business WHERE owner_id = ?";
                        $stmt = $conn->prepare($businessQuery);
                        $stmt->bind_param("i", $owner_id);
                        $stmt->execute();
                        $businessResult = $stmt->get_result();

                        while ($business = $businessResult->fetch_assoc()) {
                            $businessId = $business['id'];
                            $businessName = $business['name'];

                            $businessNewData[$businessId] = [];
                            $businessIdNameMap[$businessId] = $businessName;

                            $branchQuery = "SELECT id, location FROM branch WHERE business_id = ?";
                            $branchStmt = $conn->prepare($branchQuery);
                            $branchStmt->bind_param("i", $businessId);
                            $branchStmt->execute();
                            $branchResult = $branchStmt->get_result();

                            while ($branch = $branchResult->fetch_assoc()) {
                                $branchId = $branch['id'];
                                $location = $branch['location'];
                                $businessNewData[$businessId][] = ['id' => $branchId, 'location' => $location];
                                $branchNewData[$branchId] = $location;
                            }
                        }

                        // Get selected values from $_GET
                        $selectedBusiness = $_GET['business'] ?? 'all';
                        $selectedBranch = $_GET['branch'] ?? 'all';
                        ?>

                        <!-- new Charts -->
                        <div id="saleExpense-section" class="col-md-12 mt-2">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="salesExpensesBusinessSelect"><b>Select Business:</b></label>
                                    <select id="forecastBusinessSelect" class="form-control"
                                        onchange="handleBusinessSelect(this, 'forecastBranchSelect', 'forecastBranchSelectContainer'); updatesalesExpensesChart(this.value, 'all')">
                                        <option value="all" <?= $selectedBusiness === 'all' ? 'selected' : '' ?>>All
                                            Businesses
                                        </option>
                                        <?php foreach ($businessIdNameMap as $id => $name): ?>
                                            <option value="<?= $id ?>" <?= $selectedBusiness == $id ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($name) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3" id="forecastBranchSelectContainer">
                                    <label for="forecastBranchSelect"><b>Select Branch:</b></label>
                                    <select id="forecastBranchSelect" class="form-control"
                                        onchange="updatesalesExpensesChart(document.getElementById('forecastBusinessSelect').value, this.value)">
                                        <option value="all" <?= $selectedBranch === 'all' ? 'selected' : '' ?>>All Branches
                                        </option>
                                        <?php
                                        if ($selectedBusiness !== 'all' && isset($businessNewData[$selectedBusiness])) {
                                            foreach ($businessNewData[$selectedBusiness] as $branch) {
                                                $selected = $selectedBranch == $branch['id'] ? 'selected' : '';
                                                echo "<option value='{$branch['id']}' $selected>" . htmlspecialchars($branch['location']) . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row">

                                <!-- Financial  Overview -->
                                <div class="col-md-6 mb-4">
                                    <div class="chart-container mb-4">

                                        <h5 class="mt-2"><b><i class="fa-solid fa-chart-line"></i> Financial
                                                Overview</b>
                                            <i class="fas fa-info-circle"
                                                onclick="showInfo('Financial Overview', 'This chart shows the **monthly total of sales and expenses**, grouped by each business and its branches. Sales are based on the `total_sales` from your sales records.');">
                                            </i>
                                        </h5>
                                        <canvas id="salesAndExpensesNewChart"></canvas>
                                        <button class="btn btn-dark mt-2 mb-5" id="printChart1Button">
                                            <i class="fas fa-print me-2"></i> Generate Report
                                        </button>
                                    </div>
                                </div>
                                <!-- Sales vs Expenses Chart -->
                                <div class="col-md-6 mb-4">
                                    <div class="chart-container mb-4">
                                        <h5 class="mt-2"><b>Sales vs Expenses</b>
                                            <i class="fas fa-info-circle"
                                                onclick="showInfo('Sales vs Expenses', 'This chart displays **daily trends over time**. Sales data is fetched from the `sales` table using the `date` field. Expenses are grouped by their `created_at` date.');">
                                            </i>
                                        </h5>
                                        <canvas id="salesVsExpensesNewChart"></canvas>
                                        <button class="btn btn-dark mt-2 mb-5" id="printChart2Button">
                                            <i class="fas fa-print me-2"></i> Generate Report
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <script>
                                function updatesalesExpensesChart(selectedBusiness, selectedBranch) {
                                    const url = `index.php?business=${selectedBusiness}&branch=${selectedBranch}`;
                                    window.location.href = url;
                                }


                                document.addEventListener("DOMContentLoaded", () => {
                                    const business = document.getElementById("forecastBusinessSelect").value;
                                    const branch = document.getElementById("forecastBranchSelect").value;
                                    fetch(`../endpoints/chart/data.php?id=<?= $owner_id ?>&business=${business}&branch=${branch}`)
                                        .then((res) => res.json())
                                        .then((data) => {
                                            // Bar Chart
                                            new Chart(document.getElementById("salesAndExpensesNewChart"), {
                                                type: "bar",
                                                data: {
                                                    labels: data.labels,
                                                    datasets: [
                                                        {
                                                            label: "Sales (₱)",
                                                            backgroundColor: "rgba(75, 192, 192, 0.5)",
                                                            borderColor: "rgba(75, 192, 192, 1)",
                                                            borderWidth: 1,
                                                            data: data.sales,
                                                        },
                                                        {
                                                            label: "Expenses (₱)",
                                                            backgroundColor: "rgba(255, 99, 132, 0.5)",
                                                            borderColor: "rgba(255, 99, 132, 1)",
                                                            borderWidth: 1,
                                                            data: data.expenses,
                                                        },
                                                    ],
                                                },
                                                options: {
                                                    responsive: true,
                                                    plugins: {
                                                        legend: { position: "top" },
                                                        tooltip: { mode: "index", intersect: false },
                                                    },
                                                    scales: {
                                                        y: {
                                                            beginAtZero: true,
                                                            ticks: { callback: (val) => `₱${val}` },
                                                        },
                                                        x: {
                                                            ticks: {
                                                                callback: function (val) {
                                                                    let label = this.getLabelForValue(val);
                                                                    return label.length > 20 ? label.substr(0, 20) + "..." : label;
                                                                },
                                                            },
                                                        },
                                                    },
                                                },
                                            });

                                            // Line Chart
                                            new Chart(document.getElementById("salesVsExpensesNewChart"), {
                                                type: "line",
                                                data: {
                                                    labels: data.dates,
                                                    datasets: [
                                                        {
                                                            label: "Total Sales (₱)",
                                                            data: data.salesOverTime,
                                                            borderColor: "rgba(75, 192, 192, 1)",
                                                            backgroundColor: "rgba(75, 192, 192, 0.2)",
                                                            fill: true,
                                                            tension: 0.4,
                                                        },
                                                        {
                                                            label: "Total Expenses (₱)",
                                                            data: data.expensesOverTime,
                                                            borderColor: "rgba(255, 99, 132, 1)",
                                                            backgroundColor: "rgba(255, 99, 132, 0.2)",
                                                            fill: true,
                                                            tension: 0.4,
                                                        },
                                                    ],
                                                },
                                                options: {
                                                    responsive: true,
                                                    plugins: {
                                                        legend: { position: "top" },
                                                        tooltip: { mode: "index", intersect: false },
                                                    },
                                                    scales: {
                                                        y: {
                                                            beginAtZero: true,
                                                            ticks: { callback: (val) => `₱${val}` },
                                                        },
                                                    },
                                                },
                                            });
                                        });
                                });


                            </script>
                            <div id="cash-section" class="row">
                                <!-- Profit Margin Chart -->
                                <div class="row">
                                    <div class="col-md-6 mb-3 mt-3">
                                        <label for="salesExpensesBusinessSelect"><b>Select Business:</b></label>
                                        <select id="forecastBusinessSelect" class="form-control"
                                            onchange="handleBusinessSelect(this, 'forecastBranchSelect', 'forecastBranchSelectContainer'); updateProfitCashChart(this.value, 'all')">
                                            <option value="all" <?= $selectedBusiness === 'all' ? 'selected' : '' ?>>All
                                                Businesses
                                            </option>
                                            <?php foreach ($businessIdNameMap as $id => $name): ?>
                                                <option value="<?= $id ?>" <?= $selectedBusiness == $id ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($name) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3 mt-3" id="forecastBranchSelectContainer">
                                        <label for="forecastBranchSelect"><b>Select Branch:</b></label>
                                        <select id="forecastBranchSelect" class="form-control"
                                            onchange="updateProfitCashChart(document.getElementById('forecastBusinessSelect').value, this.value)">
                                            <option value="all" <?= $selectedBranch === 'all' ? 'selected' : '' ?>>All
                                                Branches
                                            </option>
                                            <?php
                                            if ($selectedBusiness !== 'all' && isset($businessNewData[$selectedBusiness])) {
                                                foreach ($businessNewData[$selectedBusiness] as $branch) {
                                                    $selected = $selectedBranch == $branch['id'] ? 'selected' : '';
                                                    echo "<option value='{$branch['id']}' $selected>" . htmlspecialchars($branch['location']) . "</option>";
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6" style="display: none;">
                                    <div class="chart-container mb-4">

                                        <h5 class="mt-2"><b>Profit Margin Trends</b></h5>
                                        <canvas id="profitMarginNewChart"></canvas>
                                        <!-- <button class="btn btn-dark mt-2 mb-5" id="printChart18Button">
                                            <i class="fas fa-print me-2"></i> Generate Report
                                        </button> -->
                                    </div>
                                </div>
                                <!-- Profit Margin -->
                                <div class="col-md-6">
                                    <div class="chart-container mb-4">
                                        <h5 class="mt-2">
                                            <b>Profit Margin Trends</b>
                                            <i class="fas fa-info-circle"
                                                onclick="showInfo('Profit Margin Trends', 'This chart displays the profit margin percentage over the past 12 months. It is calculated monthly using the formula: (Total Profit / Total Sales) × 100. Profit is derived from the difference between total sales and the product cost. This helps you understand profitability trends across time.')">
                                            </i>
                                        </h5>
                                        <canvas id="profitBarChart"></canvas>
                                        <button class="btn btn-dark mt-2 mb-5" id="printChart17Button">
                                            <i class="fas fa-print me-2"></i> Generate Report
                                        </button>
                                    </div>
                                </div>

                                <!-- Cash Flow Chart -->
                                <div class="col-md-6">
                                    <div class="chart-container mb-4">
                                        <h5 class="mt-2">
                                            <b>Monthly Cash Flow</b>
                                            <i class="fas fa-info-circle"
                                                onclick="showInfo('Monthly Cash Flow', 'This chart visualizes the total monthly cash inflow (from sales) and cash outflow (from expenses) for the last 12 months. It helps you track how much money is coming into and going out of your business or branch each month.')">
                                            </i>
                                        </h5>
                                        <canvas id="cashFlowNewChart"></canvas>
                                        <button class="btn btn-dark mt-2 mb-5" id="printChart18Button">
                                            <i class="fas fa-print me-2"></i> Generate Report
                                        </button>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <script>
                            document.addEventListener("DOMContentLoaded", function () {
                                const businessId = document.getElementById('forecastBusinessSelect').value;
                                const branchId = document.getElementById('forecastBranchSelect').value;
                                loadProfitMarginChart(businessId, branchId);
                            });
                            document.addEventListener('DOMContentLoaded', function () {
                                const business = document.getElementById('forecastBusinessSelect').value;
                                const branch = document.getElementById('forecastBranchSelect').value;
                                fetchAndRenderCharts(business, branch);
                            });
                            function updateProfitCashChart(selectedBusiness, selectedBranch) {
                                fetchAndRenderCharts(selectedBusiness, selectedBranch);
                            }

                            function loadProfitMarginChart(businessId = 'all', branchId = 'all') {
                                fetch(`../endpoints/chart/get_profit_comparison.php?id=<?= $owner_id ?>&business=${businessId}&branch=${branchId}`)
                                    .then(response => response.json())
                                    .then(data => {
                                        const ctx = document.getElementById('profitBarChart').getContext('2d');

                                        if (window.profitChart) {
                                            window.profitChart.destroy(); // destroy previous instance
                                        }

                                        window.profitChart = new Chart(ctx, {
                                            type: 'bar',
                                            data: {
                                                labels: ['Profit Margin (%)'],
                                                datasets: [{
                                                    label: 'Profit Margin (%)',
                                                    data: [data.margin],
                                                    backgroundColor: ['rgba(75, 192, 192, 0.5)'],
                                                    borderColor: ['rgba(75, 192, 192, 1)'],
                                                    borderWidth: 1,
                                                    borderRadius: 5
                                                }]
                                            },
                                            options: {
                                                responsive: true,
                                                scales: {
                                                    y: {
                                                        beginAtZero: true,
                                                        ticks: {
                                                            callback: function (value) {
                                                                return value + '%';
                                                            }
                                                        }
                                                    }
                                                },
                                                plugins: {
                                                    tooltip: {
                                                        callbacks: {
                                                            label: function (context) {
                                                                return context.dataset.label + ': ' + context.raw + '%';
                                                            }
                                                        }
                                                    },
                                                    legend: {
                                                        display: false
                                                    }
                                                }
                                            }
                                        });
                                    });
                            }


                            function fetchAndRenderCharts(businessId = 'all', branchId = 'all') {
                                const url = `../endpoints/chart/get_trends_data.php?id=<?= $owner_id ?>&business=${businessId}&branch=${branchId}`;

                                fetch(url)
                                    .then(response => response.json())
                                    .then(data => {
                                        // Destroy previous charts if they exist
                                        if (window.profitChartInstance) window.profitChartInstance.destroy();
                                        if (window.cashFlowChartInstance) window.cashFlowChartInstance.destroy();

                                        const ctxMargin = document.getElementById('profitMarginNewChart').getContext('2d');
                                        window.profitChartInstance = new Chart(ctxMargin, {
                                            type: 'bar',
                                            data: {
                                                labels: data.labels,
                                                datasets: [{
                                                    label: 'Profit Margin (%)',
                                                    data: data.margins,
                                                    backgroundColor: 'rgba(153, 102, 255, 0.3)',
                                                    borderColor: 'rgba(153, 102, 255, 1)',
                                                    borderWidth: 2
                                                }]
                                            },
                                            options: {
                                                responsive: true,
                                                scales: {
                                                    y: {
                                                        min: 0,
                                                        max: 100,
                                                        ticks: {
                                                            callback: value => value + '%'
                                                        }
                                                    }
                                                },
                                                plugins: {
                                                    tooltip: {
                                                        callbacks: {
                                                            label: context => `${context.dataset.label}: ${context.formattedValue}%`
                                                        }
                                                    }
                                                }
                                            }
                                        });

                                        const ctxCash = document.getElementById('cashFlowNewChart').getContext('2d');
                                        window.cashFlowChartInstance = new Chart(ctxCash, {
                                            type: 'line',
                                            data: {
                                                labels: data.labels,
                                                datasets: [
                                                    {
                                                        label: 'Total Cash Inflow (₱)',
                                                        data: data.sales,
                                                        borderColor: 'rgba(75, 192, 192, 1)',
                                                        backgroundColor: 'rgba(75, 192, 192, 0.3)',
                                                        fill: false,
                                                        tension: 0.4
                                                    },
                                                    {
                                                        label: 'Total Cash Outflow (₱)',
                                                        data: data.expenses,
                                                        borderColor: 'rgba(255, 99, 132, 1)',
                                                        backgroundColor: 'rgba(255, 99, 132, 0.3)',
                                                        fill: false,
                                                        tension: 0.4
                                                    }
                                                ]
                                            },
                                            options: {
                                                scales: {
                                                    y: {
                                                        ticks: {
                                                            callback: value => '₱' + value
                                                        }
                                                    }
                                                }
                                            }
                                        });
                                    });
                            }



                            function updateProfitCashChart(selectedBusiness, selectedBranch) {
                                const url = `index.php?business=${selectedBusiness}&branch=${selectedBranch}#cash-section`;
                                window.location.href = url;
                            }
                        </script>

                        <?php



                        ?>
                        <div id="comparison-section" class="col-md-12 mt-5 mb-5">
                            <h1>
                                <b>
                                    <i class="fa-solid fa-chart-line"></i> Business Comparison
                                    <i class="fas fa-info-circle" onclick="showInfo(
    'Business Comparison',
    'This section provides two charts:\n\n' +
    '• The Business Performance Comparison shows each business’s total sales and expenses. These are calculated by summing all sales and expenses from the business itself and all its branches.\n\n' +
    '• The Revenue Contribution chart shows how much revenue each business contributed to the total. Each business’s total sales are divided by the overall sales across all businesses, then multiplied by 100 to get the percentage.'
);">
                                    </i>

                                    </i>
                                </b>
                            </h1>

                            <div class="row">
                                <div class="col-md-6 mb-3 mt-3">
                                    <label for="salesExpensesBusinessSelect"><b>Select Business:</b></label>
                                    <select id="forecastBusinessSelect" class="form-control"
                                        onchange="handleBusinessSelect(this, 'forecastBranchSelect', 'forecastBranchSelectContainer'); updateBusinessComparison(this.value, 'all')">
                                        <option value="all" <?= $selectedBusiness === 'all' ? 'selected' : '' ?>>All
                                            Businesses
                                        </option>
                                        <?php foreach ($businessIdNameMap as $id => $name): ?>
                                            <option value="<?= $id ?>" <?= $selectedBusiness == $id ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($name) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3 mt-3" id="forecastBranchSelectContainer">
                                    <label for="forecastBranchSelect"><b>Select Branch:</b></label>
                                    <select id="forecastBranchSelect" class="form-control"
                                        onchange="updateBusinessComparison(document.getElementById('forecastBusinessSelect').value, this.value)">
                                        <option value="all" <?= $selectedBranch === 'all' ? 'selected' : '' ?>>All
                                            Branches
                                        </option>
                                        <?php
                                        if ($selectedBusiness !== 'all' && isset($businessNewData[$selectedBusiness])) {
                                            foreach ($businessNewData[$selectedBusiness] as $branch) {
                                                $selected = $selectedBranch == $branch['id'] ? 'selected' : '';
                                                echo "<option value='{$branch['id']}' $selected>" . htmlspecialchars($branch['location']) . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <!-- Business Performance Chart -->
                                <div class="col-md-6">
                                    <div class="chart-container mb-4">
                                        <h5><b>Business Performance Comparison </b></h5>
                                        <canvas id="businessPerformanceComparisonChart"></canvas>
                                        <button class="btn btn-dark mt-2 mb-5" id="printChart3Button">
                                            <i class="fas fa-print me-2"></i> Generate Report
                                        </button>
                                    </div>
                                </div>

                                <!-- Revenue Contribution Chart -->
                                <div class="col-md-6">
                                    <div class="chart-container mb-4">
                                        <h5><b>Revenue Contribution by Business</b></h5>
                                        <canvas id="revenueContributionBusinessChart"></canvas>
                                        <button class="btn btn-dark mt-2 mb-5" id="printChart4Button">
                                            <i class="fas fa-print me-2"></i> Generate Report
                                        </button>
                                    </div>
                                </div>


                                <div class="col-md-6" style="display: none">
                                    <div class="chart-container mb-4">
                                        <h5><b>Business Performance Comparison </b></h5>
                                        <canvas id="businessPerformanceChart"></canvas>
                                        <button class="btn btn-dark mt-2 mb-5" id="printChart3Button">
                                            <i class="fas fa-print me-2"></i> Generate Report
                                        </button>
                                    </div>
                                </div>


                                <div class="col-md-6" style="display: none">
                                    <div class="chart-container mb-4">
                                        <h5><b>Revenue Contribution by Business</b></h5>
                                        <canvas id="revenueContributionChart"></canvas>
                                        <button class="btn btn-dark mt-2 mb-5" id="printChart4Button">
                                            <i class="fas fa-print me-2"></i> Generate Report
                                        </button>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                const urlParams = new URLSearchParams(window.location.search);
                                const business = urlParams.get('business') || 'all';
                                const branch = urlParams.get('branch') || 'all';
                                const ownerId = <?= isset($_GET['id']) ? intval($_GET['id']) : 'null' ?>;
                                const fetchUrl = `../endpoints/chart/businessPerformance.php?business=${business}&branch=${branch}${ownerId ? `&id=${ownerId}` : ''}`;

                                fetch(fetchUrl)
                                    .then(response => response.json())
                                    .then(data => {
                                        const businesses = data.map(item => item.name);
                                        const salesData = data.map(item => item.sales);
                                        const expensesData = data.map(item => item.expenses);

                                        // Total revenue calculation for percentages
                                        const totalRevenue = salesData.reduce((acc, val) => acc + val, 0);

                                        // Calculate percentage per business
                                        const percentages = salesData.map(sale => ((sale / totalRevenue) * 100).toFixed(1));

                                        // Map revenue data
                                        const revenueByBusiness = {};
                                        businesses.forEach((name, index) => {
                                            revenueByBusiness[name] = salesData[index];
                                        });

                                        // Business Performance Comparison Chart (Horizontal Bar)
                                        const ctx1 = document.getElementById('businessPerformanceComparisonChart').getContext('2d');
                                        new Chart(ctx1, {
                                            type: 'bar',
                                            data: {
                                                labels: businesses,
                                                datasets: [
                                                    {
                                                        label: 'Sales (₱)',
                                                        data: salesData,
                                                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                                        borderColor: 'rgba(75, 192, 192, 1)',
                                                        borderWidth: 1,
                                                        barThickness: 30
                                                    },
                                                    {
                                                        label: 'Expenses (₱)',
                                                        data: expensesData,
                                                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                                                        borderColor: 'rgba(255, 99, 132, 1)',
                                                        borderWidth: 1,
                                                        barThickness: 30
                                                    }
                                                ]
                                            },
                                            options: {
                                                responsive: true,
                                                maintainAspectRatio: false,
                                                indexAxis: 'y',  // Horizontal bars
                                                scales: {
                                                    x: {
                                                        beginAtZero: true,
                                                        grid: {
                                                            display: true,
                                                            color: 'rgba(0, 0, 0, 0.1)'
                                                        },
                                                        ticks: {
                                                            callback: function (value) {
                                                                return '₱' + value.toLocaleString();
                                                            }
                                                        }
                                                    },
                                                    y: {
                                                        grid: {
                                                            display: false
                                                        }
                                                    }
                                                },
                                                plugins: {
                                                    legend: {
                                                        position: 'top',
                                                        align: 'start',
                                                        labels: {
                                                            boxWidth: 15,
                                                            padding: 15
                                                        }
                                                    },
                                                    tooltip: {
                                                        callbacks: {
                                                            label: function (context) {
                                                                const value = context.raw;
                                                                return `${context.dataset.label}: ₱${value.toLocaleString()}`;
                                                            }
                                                        }
                                                    }
                                                },
                                                layout: {
                                                    padding: {
                                                        top: 20,
                                                        bottom: 20,
                                                        left: 20,
                                                        right: 20
                                                    }
                                                }
                                            }
                                        });

                                        // Revenue Contribution Pie Chart
                                        const ctx2 = document.getElementById('revenueContributionBusinessChart').getContext('2d');
                                        new Chart(ctx2, {
                                            type: 'pie',
                                            data: {
                                                labels: businesses.map((b, i) => `${b} (${percentages[i]}%)`),
                                                datasets: [{
                                                    data: Object.values(revenueByBusiness),
                                                    backgroundColor: [
                                                        'rgba(75, 192, 192, 0.2)',
                                                        'rgba(255, 99, 132, 0.2)',
                                                        'rgba(153, 102, 255, 0.2)',
                                                        'rgba(255, 206, 86, 0.2)',
                                                        'rgba(54, 162, 235, 0.2)'
                                                    ],
                                                    borderColor: [
                                                        'rgb(75, 192, 192)',
                                                        'rgb(255, 99, 132)',
                                                        'rgb(153, 102, 255)',
                                                        'rgb(255, 206, 86)',
                                                        'rgb(54, 162, 235)'
                                                    ],
                                                    borderWidth: 1
                                                }]
                                            },
                                            options: {
                                                responsive: true,
                                                maintainAspectRatio: false,
                                                plugins: {
                                                    legend: {
                                                        position: 'right',
                                                        labels: {
                                                            boxWidth: 20
                                                        }
                                                    },
                                                    tooltip: {
                                                        callbacks: {
                                                            label: function (context) {
                                                                const value = context.raw;
                                                                const percentage = ((value / totalRevenue) * 100).toFixed(1);
                                                                return `₱${value.toLocaleString()} (${percentage}%)`;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        });
                                    })
                                    .catch(error => console.error('Error fetching chart data:', error));
                            });
                        </script>

                        <!-- // 6 -->
                        <div id="category-section" class="col-md-12 mt-5">
                            <h1>
                                <b>
                                    <i class="fa-solid fa-chart-pie mt-5"></i> Expense Breakdown
                                    <i class="fas fa-info-circle" onclick="showInfo(
        'Expense Breakdown',
        'This section provides two expense-related charts:\n\n' +
        '• The Category-Wise Expenses chart shows the total expenses grouped by either business names or branch locations. Each amount is calculated by summing all expenses under that specific category.\n\n' +
        '• The Recurring vs. One-Time Expenses chart displays monthly totals. For each month, all expenses labeled as recurring or one-time are summed up separately and stacked to show their contribution to the month’s total expenses.'
    );">
                                    </i>

                                </b>
                            </h1>
                            <div class="row">
                                <div class="col-md-6 mb-3 mt-3">
                                    <label for="salesExpensesBusinessSelect"><b>Select Business:</b></label>
                                    <select id="forecastBusinessSelect" class="form-control"
                                        onchange="handleBusinessSelect(this, 'forecastBranchSelect', 'forecastBranchSelectContainer'); updateCategoryExpense(this.value, 'all')">
                                        <option value="all" <?= $selectedBusiness === 'all' ? 'selected' : '' ?>>All
                                            Businesses
                                        </option>
                                        <?php foreach ($businessIdNameMap as $id => $name): ?>
                                            <option value="<?= $id ?>" <?= $selectedBusiness == $id ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($name) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3 mt-3" id="forecastBranchSelectContainer">
                                    <label for="forecastBranchSelect"><b>Select Branch:</b></label>
                                    <select id="forecastBranchSelect" class="form-control"
                                        onchange="updateCategoryExpense(document.getElementById('forecastBusinessSelect').value, this.value)">
                                        <option value="all" <?= $selectedBranch === 'all' ? 'selected' : '' ?>>All
                                            Branches
                                        </option>
                                        <?php
                                        if ($selectedBusiness !== 'all' && isset($businessNewData[$selectedBusiness])) {
                                            foreach ($businessNewData[$selectedBusiness] as $branch) {
                                                $selected = $selectedBranch == $branch['id'] ? 'selected' : '';
                                                echo "<option value='{$branch['id']}' $selected>" . htmlspecialchars($branch['location']) . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Category-Wise Expenses -->
                                <div class="col-md-6">
                                    <div class="chart-container mb-4" style="height: 450px;">
                                        <h5><b>Category-Wise Expenses</b></h5>
                                        <canvas id="categoryExpenseChart"></canvas>
                                        <button class="btn btn-dark mt-2 mb-5" id="printChart5Button">
                                            <i class="fas fa-print me-2"></i> Generate Report
                                        </button>
                                    </div>
                                </div>

                                <!-- Recurring vs. One-Time Expenses -->
                                <div class="col-md-6">
                                    <div class="chart-container mb-4" style="height: 450px;">
                                        <h5><b>Recurring vs. One-Time Expenses</b></h5>
                                        <canvas id="recurringExpenseChart"></canvas>
                                        <button class="btn btn-dark mt-2 mb-5" id="printChart6Button">
                                            <i class="fas fa-print me-2"></i> Generate Report
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- // 7 -->
                        <!-- Inventory Chart Section -->
                        <div class="col-md-12 mt-5" id="inventory-section">
                            <h1>
                                <b>
                                    <i class="fa-solid fa-boxes-stacked mt-5"></i> Inventory Product Sold
                                    <i class="fas fa-info-circle" onclick="showInfo(
        'Inventory Product Sold',
        'This chart displays the top 10 products with the highest quantity sold.\n\n' +
        'The data is filtered based on the selected business and branch, then sorted by total quantity sold across all recorded transactions.\n\n' +
        'Only the top 10 products are shown, with each bar representing the number of units sold for a specific product.'
    );">
                                    </i>

                                </b>
                            </h1>

                            <div class="row">
                                <div class="col-md-6 mb-3 mt-3">
                                    <label for="salesExpensesBusinessSelect"><b>Select Business:</b></label>
                                    <select id="forecastBusinessSelect" class="form-control"
                                        onchange="handleBusinessSelect(this, 'forecastBranchSelect', 'forecastBranchSelectContainer'); updateInventorySold(this.value, 'all')">
                                        <option value="all" <?= $selectedBusiness === 'all' ? 'selected' : '' ?>>All
                                            Businesses
                                        </option>
                                        <?php foreach ($businessIdNameMap as $id => $name): ?>
                                            <option value="<?= $id ?>" <?= $selectedBusiness == $id ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($name) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3 mt-3" id="forecastBranchSelectContainer">
                                    <label for="forecastBranchSelect"><b>Select Branch:</b></label>
                                    <select id="forecastBranchSelect" class="form-control"
                                        onchange="updateInventorySold(document.getElementById('forecastBusinessSelect').value, this.value)">
                                        <option value="all" <?= $selectedBranch === 'all' ? 'selected' : '' ?>>All
                                            Branches
                                        </option>
                                        <?php
                                        if ($selectedBusiness !== 'all' && isset($businessNewData[$selectedBusiness])) {
                                            foreach ($businessNewData[$selectedBusiness] as $branch) {
                                                $selected = $selectedBranch == $branch['id'] ? 'selected' : '';
                                                echo "<option value='{$branch['id']}' $selected>" . htmlspecialchars($branch['location']) . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="chart-container mb-4">
                                        <h5><b>Top Selling Products</b></h5>
                                        <canvas id="stockLevelSoldChart"></canvas>
                                        <button class="btn btn-dark mt-2 mb-5" id="printChart7Button">
                                            <i class="fas fa-print me-2"></i> Generate Report
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>



                        <div id="kpi-section" class="col-md-12 mt-5">
                            <h1><b><i class="fa-solid fa-chart-line mt-5"></i> Key Performance Indicators (KPIs) <i
                                        class="fas fa-info-circle" onclick="showInfo(
       'Key Performance Indicators',
       'This section shows financial KPIs to evaluate business performance:\n\n' +
       '• Total Sales – Sum of all recorded sales for the selected business or branch.\n' +
       '• Total Expenses – Sum of all expenses related to the selected business or branch.\n' +
       '• Revenue Growth – Compares current month sales against previous month sales.\n' +
       '• Gross Profit % – Calculated as: (Total Sales - Total Expenses) ÷ Total Sales × 100.\n' +
       '• Return on Investment (ROI) – Calculated as: (Total Sales - Total Expenses) ÷ Total Expenses × 100.'
   );">
                                    </i>
                                </b>
                            </h1>

                            <div class="row mt-3">
                                <div class="col-md-6 mb-3">
                                    <label for="kpiBusinessSelect"><b>Select Business:</b></label>
                                    <select id="kpiBusinessSelect" class="form-control"
                                        onchange="handleBusinessSelect(this, 'kpiBranchSelect', 'kpiBranchSelectContainer'); updateKPIChart(this.value, 'all')">
                                        <option value="all" <?= $selectedBusiness === 'all' ? 'selected' : '' ?>>All
                                            Businesses
                                        </option>
                                        <?php foreach ($businessIdNameMap as $id => $name): ?>
                                            <option value="<?= $id ?>" <?= $selectedBusiness == $id ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($name) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3" id="kpiBranchSelectContainer">
                                    <label for="kpiBranchSelect"><b>Select Branch:</b></label>
                                    <select id="kpiBranchSelect" class="form-control"
                                        onchange="updateKPIChart(document.getElementById('kpiBusinessSelect').value, this.value)">
                                        <option value="all" <?= $selectedBranch === 'all' ? 'selected' : '' ?>>All Branches
                                        </option>
                                        <?php
                                        if ($selectedBusiness !== 'all' && isset($businessNewData[$selectedBusiness])) {
                                            foreach ($businessNewData[$selectedBusiness] as $branch) {
                                                $selected = $selectedBranch == $branch['id'] ? 'selected' : '';
                                                echo "<option value='{$branch['id']}' $selected>" . htmlspecialchars($branch['location']) . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>

                            </div>
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
                        <script>


                        </script>

                        <!-- // 9 -->
                        <div id="forecast-section" class="col-md-12 mt-5">
                            <h1>
                                <b>
                                    <i class="fa-solid fa-chart-line"></i> Forecasting & Predictions
                                    <i class="fas fa-info-circle" onclick="showInfo(
    'Forecasting & Predictions',
    'These charts use past monthly sales and expense data to forecast the next three months.\n\n' +
    '• Future values are estimated based on the average of previous months.\n' +
    '• Predicted growth is applied at 5%, 10%, and 15% over the average for each of the next three months.\n' +
    '• This helps identify trends and anticipate upcoming business performance.\n\n' +
    'Sales and expense forecasts are shown side-by-side to help you compare future projections.'
);">
                                    </i>

                                </b>
                            </h1>

                            <div class="row mt-3">
                                <div class="col-md-6 mb-3">
                                    <label for="forecastBusinessSelect"><b>Select Business:</b></label>
                                    <select id="forecastBusinessSelect" class="form-control"
                                        onchange="handleBusinessSelect(this, 'forecastBranchSelect', 'forecastBranchSelectContainer'); updateForecastChart(this.value, 'all')">
                                        <option value="all" <?= $selectedBusiness === 'all' ? 'selected' : '' ?>>All
                                            Businesses
                                        </option>
                                        <?php foreach ($businessIdNameMap as $id => $name): ?>
                                            <option value="<?= $id ?>" <?= $selectedBusiness == $id ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($name) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3" id="forecastBranchSelectContainer">
                                    <label for="forecastBranchSelect"><b>Select Branch:</b></label>
                                    <select id="forecastBranchSelect" class="form-control"
                                        onchange="updateForecastChart(document.getElementById('forecastBusinessSelect').value, this.value)">
                                        <option value="all" <?= $selectedBranch === 'all' ? 'selected' : '' ?>>All Branches
                                        </option>
                                        <?php
                                        if ($selectedBusiness !== 'all' && isset($businessNewData[$selectedBusiness])) {
                                            foreach ($businessNewData[$selectedBusiness] as $branch) {
                                                $selected = $selectedBranch == $branch['id'] ? 'selected' : '';
                                                echo "<option value='{$branch['id']}' $selected>" . htmlspecialchars($branch['location']) . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <!-- Sales Forecast -->
                                <div class="col-md-6">
                                    <div class="chart-container mb-4">
                                        <h5><b>Sales Forecast</b></h5>
                                        <canvas id="salesForecastChart"></canvas>
                                        <button class="btn btn-dark mt-2 mb-5" id="printChart8Button">
                                            <i class="fas fa-print me-2"></i> Generate Report
                                        </button>
                                    </div>
                                </div>

                                <!-- Expense Forecast -->
                                <div class="col-md-6">
                                    <div class="chart-container mb-4">
                                        <h5><b>Expense Forecast</b></h5>
                                        <canvas id="expenseForecastChart"></canvas>
                                        <button class="btn btn-dark mt-2 mb-5" id="printChart9Button">
                                            <i class="fas fa-print me-2"></i> Generate Report
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <script>
                            const businessBranchMap = <?= json_encode($businessNewData) ?>;

                            function handleBusinessSelect(selectEl, branchSelectId, branchContainerId) {
                                const selectedBusiness = selectEl.value;
                                const branchSelect = document.getElementById(branchSelectId);

                                branchSelect.innerHTML = `<option value="all">All Branches</option>`;

                                if (selectedBusiness !== 'all' && businessBranchMap[selectedBusiness]) {
                                    businessBranchMap[selectedBusiness].forEach(branch => {
                                        const option = document.createElement("option");
                                        option.value = branch.id;
                                        option.textContent = branch.location;
                                        branchSelect.appendChild(option);
                                    });
                                }
                            }

                            function updateKPIChart(selectedBusiness, selectedBranch) {
                                const url = `index.php?business=${selectedBusiness}&branch=${selectedBranch}#kpi-section`;
                                window.location.href = url;
                            }

                            function updateForecastChart(selectedBusiness, selectedBranch) {
                                const url = `index.php?business=${selectedBusiness}&branch=${selectedBranch}#forecast-section`;
                                window.location.href = url;
                            }

                            function updateBusinessComparison(selectedBusiness, selectedBranch) {
                                const url = `index.php?business=${selectedBusiness}&branch=${selectedBranch}#comparison-section`;
                                window.location.href = url;
                            }
                            // 6
                            function updateCategoryExpense(selectedBusiness, selectedBranch) {
                                const url = `index.php?business=${selectedBusiness}&branch=${selectedBranch}#category-section`;
                                window.location.href = url;
                            }
                            function updateInventorySold(selectedBusiness, selectedBranch) {
                                const url = `index.php?business=${selectedBusiness}&branch=${selectedBranch}#inventory-section`;
                                window.location.href = url;
                            }

                        </script>

                        <div id="service-section" class="col-md-12 mt-5">
                            <h1><b><i class="fa-solid fa-box"></i> Product/Service Analysis
                                    <i class="fas fa-info-circle" onclick="showInfo(
        'Product/Service Analysis',
        'This section highlights the best and worst performing products or services based on total revenue.\n\n' +
        '• The left chart shows top-selling items with the highest revenue.\n' +
        '• The right chart displays low-performing products with minimal sales.\n' +
        '• Bars are color-coded based on the associated business.\n\n' +
        'Use this insight to evaluate which offerings contribute most—and least—to your business earnings.'
    );">
                                    </i>

                                </b>
                            </h1>
                            <div class="row mt-3">
                                <div class="col-md-6 mb-3">
                                    <label for="forecastBusinessSelect"><b>Select Business:</b></label>
                                    <select id="forecastBusinessSelect" class="form-control"
                                        onchange="handleBusinessSelect(this, 'forecastBranchSelect', 'forecastBranchSelectContainer'); updateServiceAnalysis(this.value, 'all')">
                                        <option value="all" <?= $selectedBusiness === 'all' ? 'selected' : '' ?>>All
                                            Businesses
                                        </option>
                                        <?php foreach ($businessIdNameMap as $id => $name): ?>
                                            <option value="<?= $id ?>" <?= $selectedBusiness == $id ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($name) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3" id="forecastBranchSelectContainer">
                                    <label for="forecastBranchSelect"><b>Select Branch:</b></label>
                                    <select id="forecastBranchSelect" class="form-control"
                                        onchange="updateServiceAnalysis(document.getElementById('forecastBusinessSelect').value, this.value)">
                                        <option value="all" <?= $selectedBranch === 'all' ? 'selected' : '' ?>>All Branches
                                        </option>
                                        <?php
                                        if ($selectedBusiness !== 'all' && isset($businessNewData[$selectedBusiness])) {
                                            foreach ($businessNewData[$selectedBusiness] as $branch) {
                                                $selected = $selectedBranch == $branch['id'] ? 'selected' : '';
                                                echo "<option value='{$branch['id']}' $selected>" . htmlspecialchars($branch['location']) . "</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <!-- // Top Products Chart -->
                            <div class="row">
                                <!-- Top-Selling Products Chart New -->
                                <div class="col-md-6">
                                    <div class="chart-container mb-4">
                                        <h5><b>Top-Selling Products/Services</b></h5>
                                        <canvas id="topSellingProductsChart"></canvas>
                                        <button class="btn btn-dark mt-2 mb-5" id="printChart10Button">
                                            <i class="fas fa-print me-2"></i> Generate Report
                                        </button>
                                    </div>
                                </div>

                                <!-- Low-Performing Products Chart New -->
                                <div class="col-md-6">
                                    <div class="chart-container mb-4">
                                        <h5><b>Low-Performing Products/Services</b></h5>
                                        <canvas id="lowSellingProductsChart"></canvas>
                                        <button class="btn btn-dark mt-2 mb-5" id="printChart11Button">
                                            <i class="fas fa-print me-2"></i> Generate Report
                                        </button>
                                    </div>
                                </div>

                                <!-- Product Profitability Chart New -->

                                <div class="col-md-12 mt-5 mb-2">
                                    <div class="chart-container mb-4">
                                        <h5 class="mb-3">
                                            <b>
                                                Product/Service Profitability Analysis
                                                <i class="fas fa-info-circle" onclick="showInfo(
      'Product/Service Profitability Analysis',
      'This chart analyzes the profitability of each product or service based on actual sales data.\n\n' +
      '• Each point represents a product, plotted by its revenue (₱) on the X-axis and profit (₱) on the Y-axis.\n' +
      '• Sales are correctly attributed based on whether they come from a specific branch (type: branch) or directly from the main business (type: business).\n' +
      '• Revenue is the total sales amount for the product over the last 30 days.\n' +
      '• Profit is calculated as: (Total Sales) - (Product Price × Units Sold).\n' +
      '• Hover over a point to view detailed information like product name, business, revenue, and profit.\n\n' +
      'This analysis helps you understand which products or services are the most profitable, per branch or business level.'
    );"></i>
                                            </b>
                                        </h5>

                                        <canvas id="productServiceProfitabilityChart"></canvas>
                                        <button class="btn btn-dark mt-2 mb-5" id="printChart12Button">
                                            <i class="fas fa-print me-2"></i> Generate Report
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <script>

                                function updateServiceAnalysis(selectedBusiness, selectedBranch) {
                                    const url = `index.php?business=${selectedBusiness}&branch=${selectedBranch}#service-section`;
                                    window.location.href = url;
                                }

                                document.addEventListener('DOMContentLoaded', function () {
                                    const urlParams = new URLSearchParams(window.location.search);
                                    const selectedBusiness = urlParams.get('business') || 'all';
                                    const selectedBranch = urlParams.get('branch') || 'all';

                                    fetch(`../endpoints/chart/topAndLowSales.php?business=${selectedBusiness}&branch=${selectedBranch}`)
                                        .then(response => response.json())
                                        .then(data => {
                                            renderTopSellingChart(data.topSelling);
                                            renderLowSellingChart(data.lowSelling);
                                        })
                                        .catch(error => console.error('Error:', error));

                                    const colorPalette = [
                                        'rgba(75, 192, 192, 0.6)', // Teal
                                        'rgba(255, 99, 132, 0.6)', // Red
                                        'rgba(54, 162, 235, 0.6)', // Blue
                                        'rgba(255, 206, 86, 0.6)', // Yellow
                                        'rgba(153, 102, 255, 0.6)', // Purple
                                        'rgba(255, 159, 64, 0.6)', // Orange
                                        'rgba(199, 199, 199, 0.6)', // Gray
                                        'rgba(83, 102, 255, 0.6)', // Indigo
                                        'rgba(40, 167, 69, 0.6)', // Green
                                        'rgba(220, 53, 69, 0.6)'  // Dark Red
                                    ];

                                    function generateColors(length) {
                                        let colors = [];
                                        for (let i = 0; i < length; i++) {
                                            colors.push(colorPalette[i % colorPalette.length]);
                                        }
                                        return colors;
                                    }

                                    function generateBorderColors(length) {
                                        let borderColors = [];
                                        for (let i = 0; i < length; i++) {
                                            borderColors.push(colorPalette[i % colorPalette.length].replace('0.6', '1'));
                                        }
                                        return borderColors;
                                    }

                                    function renderTopSellingChart(topProducts) {
                                        const ctx = document.getElementById('topSellingProductsChart').getContext('2d');
                                        new Chart(ctx, {
                                            type: 'bar',
                                            data: {
                                                labels: topProducts.map(p => p.name),
                                                datasets: [{
                                                    label: 'Revenue',
                                                    data: topProducts.map(p => p.total_sales),
                                                    backgroundColor: generateColors(topProducts.length),
                                                    borderColor: generateBorderColors(topProducts.length),
                                                    borderWidth: 1
                                                }]
                                            },
                                            options: {
                                                responsive: true,
                                                maintainAspectRatio: false,
                                                indexAxis: 'y',
                                                scales: {
                                                    x: {
                                                        beginAtZero: true,
                                                        ticks: {
                                                            callback: function (value) {
                                                                return '₱' + value.toLocaleString();
                                                            }
                                                        }
                                                    },
                                                    y: {
                                                        grid: {
                                                            display: false
                                                        }
                                                    }
                                                },
                                                plugins: {
                                                    legend: {
                                                        display: false
                                                    },
                                                    tooltip: {
                                                        callbacks: {
                                                            label: function (context) {
                                                                const value = context.raw;
                                                                return `Revenue: ₱${value.toLocaleString()}`;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        });
                                    }

                                    function renderLowSellingChart(lowProducts) {
                                        const ctx = document.getElementById('lowSellingProductsChart').getContext('2d');
                                        new Chart(ctx, {
                                            type: 'bar',
                                            data: {
                                                labels: lowProducts.map(p => p.name),
                                                datasets: [{
                                                    label: 'Revenue',
                                                    data: lowProducts.map(p => p.total_sales),
                                                    backgroundColor: generateColors(lowProducts.length),
                                                    borderColor: generateBorderColors(lowProducts.length),
                                                    borderWidth: 1
                                                }]
                                            },
                                            options: {
                                                responsive: true,
                                                maintainAspectRatio: false,
                                                indexAxis: 'y',
                                                scales: {
                                                    x: {
                                                        beginAtZero: true,
                                                        ticks: {
                                                            callback: function (value) {
                                                                return '₱' + value.toLocaleString();
                                                            }
                                                        }
                                                    },
                                                    y: {
                                                        grid: {
                                                            display: false
                                                        }
                                                    }
                                                },
                                                plugins: {
                                                    legend: {
                                                        display: false
                                                    },
                                                    tooltip: {
                                                        callbacks: {
                                                            label: function (context) {
                                                                const value = context.raw;
                                                                return `Revenue: ₱${value.toLocaleString()}`;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        });
                                    }
                                });

                                document.addEventListener('DOMContentLoaded', function () {
                                    const colorPalette = [
                                        'rgba(75, 192, 192, 0.6)', // Teal
                                        'rgba(255, 99, 132, 0.6)', // Red
                                        'rgba(54, 162, 235, 0.6)', // Blue
                                        'rgba(255, 206, 86, 0.6)', // Yellow
                                        'rgba(153, 102, 255, 0.6)', // Purple
                                        'rgba(255, 159, 64, 0.6)', // Orange
                                        'rgba(199, 199, 199, 0.6)', // Gray
                                        'rgba(83, 102, 255, 0.6)', // Indigo
                                        'rgba(40, 167, 69, 0.6)', // Green
                                        'rgba(220, 53, 69, 0.6)'  // Dark Red
                                    ];

                                    const urlParams = new URLSearchParams(window.location.search);
                                    const selectedBusiness = urlParams.get('business') || 'all';
                                    const selectedBranch = urlParams.get('branch') || 'all';

                                    // Load profitability data
                                    fetch(`../endpoints/chart/productProfitability.php?business=${selectedBusiness}&branch=${selectedBranch}`)
                                        .then(response => response.json())
                                        .then(data => {
                                            renderProfitabilityChart(data);
                                        })
                                        .catch(error => console.error('Error:', error));

                                    function renderProfitabilityChart(products) {
                                        const ctx = document.getElementById('productServiceProfitabilityChart').getContext('2d');

                                        // Destroy existing chart
                                        if (ctx.chart) ctx.chart.destroy();

                                        // Group products by business
                                        const businessGroups = products.reduce((acc, product) => {
                                            const key = product.business_name;
                                            if (!acc[key]) {
                                                acc[key] = [];
                                            }
                                            acc[key].push(product);
                                            return acc;
                                        }, {});

                                        // Create datasets for each business
                                        const datasets = Object.entries(businessGroups).map(([business, products], index) => ({
                                            label: business,
                                            data: products.map(p => ({
                                                x: p.revenue,
                                                y: p.profit,
                                                product: p.product_name,
                                                business: p.business_name
                                            })),
                                            backgroundColor: colorPalette[index % colorPalette.length],
                                            borderColor: colorPalette[index % colorPalette.length].replace('0.6', '1'),
                                            borderWidth: 1,
                                            pointRadius: 6,
                                            pointHoverRadius: 8
                                        }));

                                        ctx.chart = new Chart(ctx, {
                                            type: 'scatter',
                                            data: { datasets },
                                            options: {
                                                responsive: true,
                                                maintainAspectRatio: false,
                                                scales: {
                                                    x: {
                                                        type: 'linear',
                                                        position: 'bottom',
                                                        title: { display: true, text: 'Revenue (₱)' },
                                                        ticks: {
                                                            callback: value => `₱${value.toLocaleString()}`
                                                        }
                                                    },
                                                    y: {
                                                        title: { display: true, text: 'Profit (₱)' },
                                                        ticks: {
                                                            callback: value => `₱${value.toLocaleString()}`
                                                        }
                                                    }
                                                },
                                                plugins: {
                                                    tooltip: {
                                                        callbacks: {
                                                            title: context => context[0].raw.product,
                                                            label: context => [
                                                                `Business: ${context.raw.business}`,
                                                                `Revenue: ₱${context.raw.x.toLocaleString()}`,
                                                                `Profit: ₱${context.raw.y.toLocaleString()}`
                                                            ]
                                                        }
                                                    },
                                                    legend: {
                                                        position: 'bottom',
                                                        labels: {
                                                            boxWidth: 12,
                                                            padding: 20
                                                        }
                                                    }
                                                }
                                            }
                                        });
                                    }
                                });
                            </script>

                            <!-- Top-Selling Products Chart -->
                            <div class="col-md-6" style="display: none">
                                <div class="chart-container mb-4">
                                    <h5><b>Top-Selling Products/Services</b></h5>
                                    <canvas id="topProductsChart"></canvas>
                                    <button class="btn btn-dark mt-2 mb-5" id="printChart10Button">
                                        <i class="fas fa-print me-2"></i> Generate Report
                                    </button>
                                </div>
                            </div>

                            <!-- Low-Performing Products Chart -->
                            <div class="col-md-6" style="display: none">
                                <div class="chart-container mb-4">
                                    <h5><b>Low-Performing Products/Services</b></h5>
                                    <canvas id="lowProductsChart"></canvas>
                                    <button class="btn btn-dark mt-2 mb-5" id="printChart11Button">
                                        <i class="fas fa-print me-2"></i> Generate Report
                                    </button>
                                </div>
                            </div>

                            <!-- Product Profitability Chart -->
                            <div class="col-md-12 mt-5 mb-5" style="display: none">
                                <div class="chart-container mb-4">
                                    <h5 class="mt-5 mb-3"><b>Product/Service Profitability Analysis
                                            <i class="fas fa-info-circle" onclick="">
                                            </i>

                                        </b>
                                    </h5>
                                    <canvas id="productProfitabilityChart"></canvas>
                                    <button class="btn btn-dark mt-2 mb-5" id="printChart12Button">
                                        <i class="fas fa-print me-2"></i> Generate Report
                                    </button>
                                </div>
                            </div>

                            <!-- Customer Demographics -->
                            <div id="demographics-section" class="col-md-12 mt-5 mb-5">
                                <h1>
                                    <b><i class="fa-solid fa-users mt-5"></i> Customer Demographics
                                        <i class="fas fa-info-circle" onclick="showInfo(
    'Customer Demographics', 
    'This chart visualizes product sales distribution across different business locations (main and branches).\n\n' +
    '• X-axis: Locations (e.g., branches or headquarters).\n' +
    '• Stacked bars: Represent revenue from each product sold at that location.\n' +
    '• Color-coded by product for easy comparison.\n\n' +
    'Use this to identify which products perform best in specific areas and guide inventory or marketing strategies.'
);"></i>

                                    </b>
                                </h1>
                                <div class="row mt-3">
                                    <div class="col-md-6 mb-3">
                                        <label for="forecastBusinessSelect"><b>Select Business:</b></label>
                                        <select id="forecastBusinessSelect" class="form-control"
                                            onchange="handleBusinessSelect(this, 'forecastBranchSelect', 'forecastBranchSelectContainer'); updateDemographics(this.value, 'all')">
                                            <option value="all" <?= $selectedBusiness === 'all' ? 'selected' : '' ?>>All
                                                Businesses
                                            </option>
                                            <?php foreach ($businessIdNameMap as $id => $name): ?>
                                                <option value="<?= $id ?>" <?= $selectedBusiness == $id ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($name) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3" id="forecastBranchSelectContainer">
                                        <label for="forecastBranchSelect"><b>Select Branch:</b></label>
                                        <select id="forecastBranchSelect" class="form-control"
                                            onchange="updateDemographics(document.getElementById('forecastBusinessSelect').value, this.value)">
                                            <option value="all" <?= $selectedBranch === 'all' ? 'selected' : '' ?>>All
                                                Branches
                                            </option>
                                            <?php
                                            if ($selectedBusiness !== 'all' && isset($businessNewData[$selectedBusiness])) {
                                                foreach ($businessNewData[$selectedBusiness] as $branch) {
                                                    $selected = $selectedBranch == $branch['id'] ? 'selected' : '';
                                                    echo "<option value='{$branch['id']}' $selected>" . htmlspecialchars($branch['location']) . "</option>";
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="chart-container mb-4">
                                    <h5><b>Top Products by Location</b></h5>
                                    <canvas id="demographicsChart"></canvas>
                                    <button class="btn btn-dark mt-2 mb-5" id="printChart13Button">
                                        <i class="fas fa-print me-2"></i> Generate Report
                                    </button>
                                </div>
                            </div>

                            <script>
                                const colorPalette = [
                                    'rgba(75, 192, 192, 0.6)', 'rgba(255, 99, 132, 0.6)',
                                    'rgba(54, 162, 235, 0.6)', 'rgba(255, 206, 86, 0.6)',
                                    'rgba(153, 102, 255, 0.6)', 'rgba(255, 159, 64, 0.6)',
                                    'rgba(199, 199, 199, 0.6)', 'rgba(83, 102, 255, 0.6)',
                                    'rgba(40, 167, 69, 0.6)', 'rgba(220, 53, 69, 0.6)'
                                ];

                                let demographicsChart = null;

                                function updateDemographics(selectedBusiness, selectedBranch) {
                                    const url = `index.php?business=${selectedBusiness}&branch=${selectedBranch}#demographics-section`;
                                    window.location.href = url;
                                }

                                function loadDemographicsChart(selectedBusiness, selectedBranch) {
                                    fetch(`../endpoints/chart/demographicsData.php?business=${selectedBusiness}&branch=${selectedBranch}`)
                                        .then(response => response.json())
                                        .then(data => renderDemographicsChart(data))
                                        .catch(error => console.error('Error:', error));
                                }

                                function renderDemographicsChart(data) {
                                    const ctx = document.getElementById('demographicsChart').getContext('2d');

                                    // Destroy existing chart
                                    if (demographicsChart) demographicsChart.destroy();

                                    const locations = [...new Set(data.map(d => d.location))];
                                    const products = [...new Set(data.map(d => d.product_name))];

                                    // Create color mapping
                                    const productColors = {};
                                    products.forEach((product, index) => {
                                        productColors[product] = colorPalette[index % colorPalette.length];
                                    });

                                    const datasets = products.map(product => ({
                                        label: product,
                                        data: locations.map(location => {
                                            const entry = data.find(d =>
                                                d.location === location && d.product_name === product
                                            );
                                            return entry ? entry.total_revenue : 0;
                                        }),
                                        backgroundColor: productColors[product],
                                        borderColor: productColors[product].replace('0.6', '1'),
                                        borderWidth: 1
                                    }));

                                    demographicsChart = new Chart(ctx, {
                                        type: 'bar',
                                        data: {
                                            labels: locations,
                                            datasets: datasets
                                        },
                                        options: {
                                            responsive: true,
                                            maintainAspectRatio: false,
                                            scales: {
                                                x: {
                                                    stacked: true,
                                                    title: { display: true, text: 'Location' }
                                                },
                                                y: {
                                                    stacked: true,
                                                    beginAtZero: true,
                                                    title: { display: true, text: 'Revenue (₱)' },
                                                    ticks: {
                                                        callback: value => `₱${value.toLocaleString()}`
                                                    }
                                                }
                                            },
                                            plugins: {
                                                legend: {
                                                    position: 'right',
                                                    labels: { boxWidth: 12, font: { size: 10 } }
                                                },
                                                tooltip: {
                                                    callbacks: {
                                                        label: context => {
                                                            const value = context.raw;
                                                            const product = context.dataset.label;
                                                            const purchases = data.find(d =>
                                                                d.location === context.label &&
                                                                d.product_name === product
                                                            )?.purchase_count || 0;
                                                            return [
                                                                `Product: ${product}`,
                                                                `Revenue: ₱${value.toLocaleString()}`,
                                                                `Units Sold: ${purchases}`
                                                            ];
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    });
                                }

                                // Initialize chart
                                document.addEventListener('DOMContentLoaded', function () {
                                    const urlParams = new URLSearchParams(window.location.search);
                                    const selectedBusiness = urlParams.get('business') || 'all';
                                    const selectedBranch = urlParams.get('branch') || 'all';

                                    loadDemographicsChart(selectedBusiness, selectedBranch);

                                });
                            </script>

                            <!-- Trend Analysis -->
                            <div id="trend-section" class="col-md-12 mt-5 mb-5">
                                <h1>
                                    <b>
                                        <i class="fa-solid fa-chart-line mt-5"></i> Trend Analysis
                                        <i class="fas fa-info-circle" onclick="showInfo(
       'Trend Analysis',
       'This dashboard includes two charts:\n\n' +
       '📈 **Seasonal Trends** - Tracks monthly totals for **sales** and **expenses** over the past year. Use this to detect peak months, dips, and spending patterns.\n\n' +
       '📊 **Growth Rate Analysis** - Shows the month-over-month **percentage change** in sales, expenses, and profits. Helpful for identifying periods of growth, decline, or volatility.\n\n' +
       'These visualizations support strategic planning and forecasting by making trends and seasonal behavior easier to spot.'
   );">
                                        </i>

                                    </b>
                                </h1>
                                <div class="row mt-3">
                                    <div class="col-md-6 mb-3">
                                        <label for="forecastBusinessSelect"><b>Select Business:</b></label>
                                        <select id="forecastBusinessSelect" class="form-control"
                                            onchange="handleBusinessSelect(this, 'forecastBranchSelect', 'forecastBranchSelectContainer'); updateTrend(this.value, 'all')">
                                            <option value="all" <?= $selectedBusiness === 'all' ? 'selected' : '' ?>>All
                                                Businesses
                                            </option>
                                            <?php foreach ($businessIdNameMap as $id => $name): ?>
                                                <option value="<?= $id ?>" <?= $selectedBusiness == $id ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($name) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3" id="forecastBranchSelectContainer">
                                        <label for="forecastBranchSelect"><b>Select Branch:</b></label>
                                        <select id="forecastBranchSelect" class="form-control"
                                            onchange="updateTrend(document.getElementById('forecastBusinessSelect').value, this.value)">
                                            <option value="all" <?= $selectedBranch === 'all' ? 'selected' : '' ?>>All
                                                Branches
                                            </option>
                                            <?php
                                            if ($selectedBusiness !== 'all' && isset($businessNewData[$selectedBusiness])) {
                                                foreach ($businessNewData[$selectedBusiness] as $branch) {
                                                    $selected = $selectedBranch == $branch['id'] ? 'selected' : '';
                                                    echo "<option value='{$branch['id']}' $selected>" . htmlspecialchars($branch['location']) . "</option>";
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <!-- Seasonal Trends Chart -->
                                    <div class="col-md-6">
                                        <div class="chart-container mb-4">
                                            <h5><b>Seasonal Trends</b></h5>
                                            <canvas id="seasonalTrendsChart"></canvas>
                                            <button class="btn btn-dark mt-2 mb-5" id="printChart14Button">
                                                <i class="fas fa-print me-2"></i> Generate Report
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Growth Rate Chart -->
                                    <div class="col-md-6">
                                        <div class="chart-container mb-4">
                                            <h5><b>Growth Rate Analysis</b></h5>
                                            <canvas id="growthRateChart"></canvas>
                                            <button class="btn btn-dark mt-2 mb-5" id="printChart15Button">
                                                <i class="fas fa-print me-2"></i> Generate Report
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <script>

                        let seasonalChart = null;
                        let growthChart = null;

                        function updateTrend(selectedBusiness, selectedBranch) {
                            const url = `index.php?business=${selectedBusiness}&branch=${selectedBranch}#trend-section`;
                            window.location.href = url;
                        }

                        function loadTrendCharts(selectedBusiness = 'all', selectedBranch = 'all') {
                            fetch(`../endpoints/chart/trendAnalysis.php?business=${selectedBusiness}&branch=${selectedBranch}`)
                                .then(response => response.json())
                                .then(data => {
                                    const orderedData = ensureMonthOrder(data);
                                    renderSeasonalTrends(orderedData);
                                    renderGrowthRates(orderedData);
                                })
                                .catch(error => console.error('Error loading trend charts:', error));
                        }

                        function ensureMonthOrder(data) {
                            // Ensure data is sorted chronologically and contains both months
                            const months = [
                                dateToMonthKey(new Date().getFullYear(), new Date().getMonth() - 1), // Previous month
                                dateToMonthKey(new Date().getFullYear(), new Date().getMonth())       // Current month
                            ];

                            return months.map(month => {
                                const found = data.find(d => d.month === month) || {
                                    month: month,
                                    sales: 0,
                                    expenses: 0,
                                    profit: 0
                                };
                                return {
                                    ...found,
                                    month: month // Ensure correct month format
                                };
                            });
                        }

                        function dateToMonthKey(year, month) {
                            return `${year}-${String(month + 1).padStart(2, '0')}`;
                        }

                        function renderSeasonalTrends(data) {
                            const ctx = document.getElementById('seasonalTrendsChart').getContext('2d');
                            if (window.seasonalChart) window.seasonalChart.destroy();

                            window.seasonalChart = new Chart(ctx, {
                                type: 'line',
                                data: createSeasonalChartData(data),
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            ticks: { callback: value => '₱' + value.toLocaleString() },
                                            title: { display: true, text: 'Amount (₱)' }
                                        }
                                    },
                                    plugins: {
                                        tooltip: {
                                            callbacks: { label: context => `${context.dataset.label}: ₱${context.raw.toLocaleString()}` }
                                        }
                                    }
                                }
                            });
                        }

                        function createSeasonalChartData(data) {
                            return {
                                labels: data.map(d => formatMonth(d.month)),
                                datasets: [
                                    createDataset('Sales', 'rgb(75, 192, 192)', data.map(d => d.sales)),
                                    createDataset('Expenses', 'rgb(255, 99, 132)', data.map(d => d.expenses))
                                ]
                            };
                        }

                        function createDataset(label, borderColor, data) {
                            return {
                                label: label,
                                data: data,
                                borderColor: borderColor,
                                backgroundColor: `${borderColor}20`,
                                fill: true,
                                tension: 0.4,
                                spanGaps: true // Connect lines even with missing data
                            };
                        }

                        function renderGrowthRates(data) {
                            const ctx = document.getElementById('growthRateChart').getContext('2d');
                            const growthRates = calculateGrowthRates(data);

                            if (window.growthChart) window.growthChart.destroy();

                            window.growthChart = new Chart(ctx, {
                                type: 'line',
                                data: createGrowthChartData(growthRates),
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    scales: {
                                        y: {
                                            ticks: { callback: value => value.toFixed(1) + '%' },
                                            title: { display: true, text: 'Growth Rate (%)' }
                                        }
                                    },
                                    plugins: {
                                        tooltip: {
                                            callbacks: { label: context => `${context.dataset.label}: ${context.raw.toFixed(1)}%` }
                                        }
                                    }
                                }
                            });
                        }

                        function createGrowthChartData(growthRates) {
                            return {
                                labels: growthRates.map(d => formatMonth(d.month)),
                                datasets: [
                                    createGrowthDataset('Sales Growth', 'rgb(75, 192, 192)', growthRates.map(d => d.salesGrowth)),
                                    createGrowthDataset('Expenses Growth', 'rgb(255, 99, 132)', growthRates.map(d => d.expensesGrowth)),
                                    createGrowthDataset('Profit Growth', 'rgb(153, 102, 255)', growthRates.map(d => d.profitGrowth))
                                ]
                            };
                        }

                        function createGrowthDataset(label, borderColor, data) {
                            return {
                                label: label,
                                data: data,
                                borderColor: borderColor,
                                tension: 0.4,
                                fill: false,
                                spanGaps: true
                            };
                        }

                        function formatMonth(monthString) {
                            const [year, month] = monthString.split('-');
                            const date = new Date(year, month - 1);
                            return date.toLocaleString('default', { month: 'short', year: '2-digit' });
                        }

                        function calculateGrowthRates(data) {
                            return data.map((current, index) => {
                                if (index === 0) return { month: current.month, salesGrowth: 0, expensesGrowth: 0, profitGrowth: 0 };

                                const previous = data[index - 1];
                                const safeDivide = (currentVal, prevVal) =>
                                    prevVal === 0 ? (currentVal === 0 ? 0 : 100) : ((currentVal - prevVal) / prevVal) * 100;

                                return {
                                    month: current.month,
                                    salesGrowth: Number(safeDivide(current.sales, previous.sales).toFixed(1)),
                                    expensesGrowth: Number(safeDivide(current.expenses, previous.expenses).toFixed(1)),
                                    profitGrowth: Number(safeDivide(current.profit, previous.profit).toFixed(1))
                                };
                            });
                        }

                        // Initialize charts
                        document.addEventListener('DOMContentLoaded', function () {
                            const urlParams = new URLSearchParams(window.location.search);
                            const selectedBusiness = urlParams.get('business') || 'all';
                            const selectedBranch = urlParams.get('branch') || 'all';
                            loadTrendCharts(selectedBusiness, selectedBranch);
                        });


                    </script>

                    <div id="popularProductsSection">
                        <div class="col-md-12 mt-5">
                            <h1><b><i class="fa-solid fa-boxes icon  mt-5"></i> Popular Products<i
                                        class="fas fa-info-circle"
                                        onclick="showInfo(' Popular Products', 'This graph displays all the popular products and can be filtered also by months.');"></i></b>
                            </h1>
                            <div class="col-md-12 dashboard-content">
                                <div class="mb-3">
                                    <label for="monthFilter"><b>Filter by Month
                                            (
                                            <?php echo date("Y"); ?>):</b></label>
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
                                            <th>Type <button class="btn text-white"><i class="fas fa-sort"></i></button>
                                            </th>
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
                                <button class="btn btn-primary mt-2 mb-5" onclick="printPopularProducts()">
                                    <i class="fas fa-print me-2"></i> Generate Report
                                </button>
                            </div>
                        </div>
                    </div>




                    <!-- // 10 -->
                    <div id="breach-section" class="col-md-12 mt-5">
                        <h1>
                            <b>
                                <i class="fa-solid fa-exclamation-triangle"></i> Alerts & Thresholds
                                <i class="fas fa-info-circle" onclick="showInfo(
       'Alerts & Thresholds',
       'This panel compares your **monthly expenses** to your **total assets**.\n\n' +
       '📊 **Expense Threshold Chart**:\n- Bars turn **red** when expenses exceed assets (⚠️ breach).\n- A **green line** shows your total assets as a reference.\n\n' +
       '📋 **Threshold Alerts**:\n- Shows each month’s status.\n- ✅ Low expense months are marked in green.\n- ⚠️ High expense months trigger warnings in red.\n\nThis tool helps monitor financial health and avoid overspending.'
   );">
                                </i>

                            </b>
                        </h1>
                        <div class="row mt-3">
                            <div class="col-md-6 mb-3">
                                <label for="forecastBusinessSelect"><b>Select Business:</b></label>
                                <select id="forecastBusinessSelect" class="form-control"
                                    onchange="handleBusinessSelect(this, 'forecastBranchSelect', 'forecastBranchSelectContainer'); updateBreach(this.value, 'all')">
                                    <option value="all" <?= $selectedBusiness === 'all' ? 'selected' : '' ?>>All
                                        Businesses
                                    </option>
                                    <?php foreach ($businessIdNameMap as $id => $name): ?>
                                        <option value="<?= $id ?>" <?= $selectedBusiness == $id ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($name) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3" id="forecastBranchSelectContainer">
                                <label for="forecastBranchSelect"><b>Select Branch:</b></label>
                                <select id="forecastBranchSelect" class="form-control"
                                    onchange="updateBreach(document.getElementById('forecastBusinessSelect').value, this.value)">
                                    <option value="all" <?= $selectedBranch === 'all' ? 'selected' : '' ?>>All
                                        Branches
                                    </option>
                                    <?php
                                    if ($selectedBusiness !== 'all' && isset($businessNewData[$selectedBusiness])) {
                                        foreach ($businessNewData[$selectedBusiness] as $branch) {
                                            $selected = $selectedBranch == $branch['id'] ? 'selected' : '';
                                            echo "<option value='{$branch['id']}' $selected>" . htmlspecialchars($branch['location']) . "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <!-- Expense Threshold Bar Chart -->
                            <div class="col-md-8">
                                <div class="chart-container mb-4">
                                    <h5><b>Expense Threshold Breach</b></h5>
                                    <canvas id="expenseThresholdChart"></canvas>
                                    <button class="btn btn-dark mt-2 mb-5" id="printChart16Button">
                                        <i class="fas fa-print me-2"></i> Generate Report
                                    </button>
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

                    <script>
                        function updateBreach(selectedBusiness, selectedBranch) {
                            const url = `index.php?business=${selectedBusiness}&branch=${selectedBranch}#breach-section`;
                            window.location.href = url;
                        }
                    </script>

                    <div id="recentActivitiesSection">
                        <div class="col-md-12 mt-5">
                            <h1><b><i class="fas fa-bell mt-5"></i> Activity Log <i class="fas fa-info-circle"
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
                <input type="text" id="business-name" class="form-control mb-2" placeholder="Business Name" required>
                <input type="text" id="business-description" class="form-control mb-2" placeholder="Business Description">
                <input type="number" id="business-asset" class="form-control mb-2" placeholder="Asset Size" required>
                <input type="number" id="employee-count" class="form-control mb-2" placeholder="Number of Employees" required>
                <input type="text" id="business-location" class="form-control mb-2" placeholder="Location" required>
                <div class="mt-3">
                    <label for="business-permit" class="form-label">Business Permit (Image/PDF)</label>
                    <input type="file" id="business-permit" class="form-control" accept="image/*,.pdf" required>
                    <small class="text-muted">Upload a clear image or PDF of your business permit</small>
                </div>
            </div>
        `,
                confirmButtonText: 'Add Business',
                showCancelButton: true,
                cancelButtonText: 'Skip',
                preConfirm: () => {
                    const businessName = document.getElementById('business-name').value.trim();
                    const businessAsset = document.getElementById('business-asset').value.trim();
                    const employeeCount = document.getElementById('employee-count').value.trim();
                    const location = document.getElementById('business-location').value.trim();
                    const permitFile = document.getElementById('business-permit').files[0];

                    if (!businessName || !businessAsset || !employeeCount || !location || !permitFile) {
                        Swal.showValidationMessage('Please fill in all required fields and upload business permit');
                        return false;
                    }

                    return {
                        businessName,
                        businessDescription: document.getElementById('business-description').value.trim(),
                        businessAsset,
                        employeeCount,
                        location,
                        permitFile
                    };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('name', result.value.businessName);
                    formData.append('description', result.value.businessDescription);
                    formData.append('asset', result.value.businessAsset);
                    formData.append('employeeCount', result.value.employeeCount);
                    formData.append('location', result.value.location);
                    formData.append('permit', result.value.permitFile);
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
                    // Skip business creation
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

        var recurringValues = months.map(month => {
            var total = 0;
            var monthData = expenseData.recurringByMonth[month];
            for (var category in monthData) {
                total += monthData[category].recurring.reduce((sum, val) => sum + parseFloat(val.replace('₱', '').replace(/,/g, '')), 0);
            }
            return total;
        });

        var oneTimeValues = months.map(month => {
            var total = 0;
            var monthData = expenseData.recurringByMonth[month];
            for (var category in monthData) {
                total += monthData[category].oneTime.reduce((sum, val) => sum + parseFloat(val.replace('₱', '').replace(/,/g, '')), 0);
            }
            return total;
        });

        var ctx2 = document.getElementById('recurringExpenseChart').getContext('2d');
        new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [{
                    label: 'Recurring Expenses',
                    data: recurringValues,
                    backgroundColor: 'rgba(75, 192, 192, 0.5)',
                    borderColor: 'rgb(75, 192, 192)',
                    borderWidth: 1
                }, {
                    label: 'One-Time Expenses',
                    data: oneTimeValues,
                    backgroundColor: 'rgba(255, 99, 132, 0.5)',
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
        // document.addEventListener("DOMContentLoaded", function () {
        //     const business = document.getElementById('forecastBusinessSelect').value;
        //     const branch = document.getElementById('forecastBranchSelect').value;
        //     updateInventorySold(business, branch);
        // });

        const inventoryData = <?= json_encode($inventoryData) ?>;

        // Bar Chart with colored products
        new Chart(document.getElementById('stockLevelSoldChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: inventoryData.products,
                datasets: [{
                    label: 'Units Sold',
                    data: inventoryData.stockLevelsSold,
                    backgroundColor: inventoryData.colors,
                    borderColor: inventoryData.colors.map(color => color.replace(/0\.\d+\)/, '1)')),
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Units Sold'
                        }
                    },
                    x: {
                        ticks: {
                            autoSkip: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
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
            const thresholds = JSON.parse('<?php echo $thresholdsJson; ?> ');
            const breachMonths = JSON.parse('<?php echo $breachMonthsJson; ?> ');
            const positiveMonths = JSON.parse('<?php echo $positiveMonthsJson; ?> ');
            const totalAssets = <?php echo $totalAssets; ?>; // Fetch total assets from PHP

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
                    labels: labels, // Month Names
                    datasets: [
                        {
                            label: "Monthly Expenses",
                            data: expenses,
                            backgroundColor: expenses.map((value, index) =>
                                value > totalAssets ? "red" : "blue" // Red if expenses exceed assets, blue otherwise
                            )
                        },
                        {
                            label: "Total Assets",
                            data: Array(labels.length).fill(totalAssets), // Horizontal line
                            type: "line",
                            borderColor: "green",
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

            // Generate Alerts
            const breachList = document.getElementById("breachList");
            breachList.innerHTML = ""; // Clear previous content

            Object.keys(expenseData).forEach(month => {
                const monthName = monthNames[month - 1];
                const totalExpense = expenseData[month];

                // Skip months with no expense data
                if (!totalExpense || totalExpense === 0) {
                    return;
                }

                const listItem = document.createElement("li");

                if (totalExpense < totalAssets) {
                    // Low Expenses Alert
                    listItem.className = "list-group-item list-group-item-success";
                    listItem.textContent = `✅ Low Expenses in ${monthName}`;
                } else if (totalExpense > totalAssets) {
                    // High Expenses Alert
                    listItem.className = "list-group-item list-group-item-danger";
                    listItem.textContent = `⚠️ High Expenses in ${monthName}`;
                }

                breachList.appendChild(listItem);
            });
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
    <script>
        function handleBusinessSelect(selectElement, branchSelectId, containerId) {
            const businessName = selectElement.value;
            const branchSelect = document.getElementById(branchSelectId);
            branchSelect.innerHTML = '<option value="all">All Branches</option>';

            if (businessName !== 'all' && businessBranches[businessName]) {
                Object.keys(businessBranches[businessName]).forEach(branchId => {
                    const option = document.createElement('option');
                    option.value = branchId;
                    option.textContent = businessBranches[businessName][branchId];
                    branchSelect.appendChild(option);
                });
            }
        }
        function updateFinancialNewChart(businessName, branchId) {
            const chartContainer = document.getElementById('financialOverviewNewChart').parentElement;
            chartContainer.classList.add('loading');

            fetch(`../endpoints/chart/financial_data.php?business=${encodeURIComponent(businessName)}&branch=${encodeURIComponent(branchId)}`)
                .then(response => response.json())
                .then(data => {
                    financialOverviewNewChart.data.datasets[0].data = data.sales;
                    financialOverviewNewChart.data.datasets[1].data = data.expenses;
                    financialOverviewNewChart.update();
                })
                .catch(error => console.error('Error:', error))
                .finally(() => chartContainer.classList.remove('loading'));
        }
        var businessBranches = " . json_encode($businessNewData) . ";
        var branchInfo = " . json_encode($branchNewInfo) . ";
        // 1 
        let financialOverviewNewChart;

        function initFinancialOverviewChart() {
            const ctx = document.getElementById('financialOverviewNewChart').getContext('2d');
            financialOverviewNewChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [
                        {
                            label: 'Sales (₱)',
                            data: new Array(12).fill(0),
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Expenses (₱)',
                            data: new Array(12).fill(0),
                            backgroundColor: 'rgba(255, 99, 132, 0.2)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function (value) {
                                    return '₱' + value.toLocaleString();
                                }
                            }
                        }
                    },
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    }
                }
            });

            // Load initial data
            updateFinancialNewChart('all', 'all');
        }


        // Initialize the chart when the page loads
        document.addEventListener('DOMContentLoaded', initFinancialOverviewChart);


    </script>
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

        function cloneChartForPrint(originalCanvasId, reportTitle) {
            const originalCanvas = document.getElementById(originalCanvasId);
            const chartDataUrl = originalCanvas.toDataURL();

            const printWindow = window.open('', '', 'height=600,width=800');

            printWindow.document.write(`
            <html>
                <head>
                    <title>${reportTitle}</title>
                    <style>
                        body { font-family: Arial, sans-serif; }
                        img { max-width: 100%; height: auto; }

                        @media print {
                            @page {
                                size: landscape;
                                margin: 2cm;
                            }
                            body {
                                margin: 0;
                            }
                            h1 {
                                text-align: center;
                                margin-bottom: 1cm;
                            }
                        }
                    </style>
                </head>
                <body>
                    <h1>${reportTitle}</h1>
                    <div>
                        <img src="${chartDataUrl}" alt="Chart Image" />
                    </div>
                </body>
            </html>
        `);

            printWindow.document.close();
            printWindow.focus();

            // Wait a moment to ensure content loads before printing
            setTimeout(() => {
                printWindow.print();
            }, 500);
        }

        // Attach button event listeners
        document.getElementById("printChart1Button").addEventListener("click", () => {
            cloneChartForPrint("salesAndExpensesNewChart", "Financial Overview Report");
        });

        document.getElementById("printChart2Button").addEventListener("click", () => {
            cloneChartForPrint("salesVsExpensesNewChart", "Sales vs Expenses Report");
        });

        document.getElementById("printChart3Button").addEventListener("click", () => {
            cloneChartForPrint("businessPerformanceComparisonChart", "Business Performance Report");
        });

        document.getElementById("printChart4Button").addEventListener("click", () => {
            cloneChartForPrint("revenueContributionBusinessChart", "Revenue Contribution Report");
        });

        document.getElementById("printChart5Button").addEventListener("click", () => {
            cloneChartForPrint("categoryExpenseChart", "Category Expense Report");
        });

        document.getElementById("printChart6Button").addEventListener("click", () => {
            cloneChartForPrint("recurringExpenseChart", "Recurring Expense Report");
        });

        document.getElementById("printChart7Button").addEventListener("click", () => {
            cloneChartForPrint("stockLevelSoldChart", "Stock Level Sold Report");
        });

        document.getElementById("printChart8Button").addEventListener("click", () => {
            cloneChartForPrint("salesForecastChart", "Sales Forecast Report");
        });

        document.getElementById("printChart9Button").addEventListener("click", () => {
            cloneChartForPrint("expenseForecastChart", "Expense Forecast Report");
        });

        document.getElementById("printChart10Button").addEventListener("click", () => {
            cloneChartForPrint("topSellingProductsChart", "Top Products Report");
        });

        document.getElementById("printChart11Button").addEventListener("click", () => {
            cloneChartForPrint("lowSellingProductsChart", "Low Products Report");
        });

        document.getElementById("printChart12Button").addEventListener("click", () => {
            cloneChartForPrint("productServiceProfitabilityChart", "Product Profitability Report");
        });

        document.getElementById("printChart13Button").addEventListener("click", () => {
            cloneChartForPrint("demographicsChart", "Demographics Report");
        });

        document.getElementById("printChart14Button").addEventListener("click", () => {
            cloneChartForPrint("seasonalTrendsChart", "Seasonal Trends Report");
        });

        document.getElementById("printChart15Button").addEventListener("click", () => {
            cloneChartForPrint("growthRateChart", "Growth Rate Report");
        });

        document.getElementById("printChart16Button").addEventListener("click", () => {
            cloneChartForPrint("expenseThresholdChart", "Expense Threshold Report");
        });

        document.getElementById("printChart17Button").addEventListener("click", () => {
            cloneChartForPrint("profitBarChart", "Profit Margin Report");
        });

        document.getElementById("printChart18Button").addEventListener("click", () => {
            cloneChartForPrint("cashFlowNewChart", "Cash Flow Report");
        });
    </script>
</body>

</html>