<?php
session_start();
require_once '../conn/auth.php';
require_once '../conn/conn.php';

// Validate the session to ensure only owner can access
validateSession('owner');

// Get the owner_id (from session or URL param ?id=)
$owner_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
if (isset($_GET['id']) && intval($_GET['id']) > 0) {
    $owner_id = intval($_GET['id']);
}

// Get filters from URL
$selectedBusiness = isset($_GET['business']) && $_GET['business'] !== 'all' ? intval($_GET['business']) : 'all';
$selectedBranch = isset($_GET['branch']) && $_GET['branch'] !== 'all' ? intval($_GET['branch']) : 'all';

// Fetch businesses
$businesses = [];

if ($selectedBusiness === 'all') {
    $query = "SELECT id AS business_id, name FROM business WHERE owner_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $owner_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($business = $result->fetch_assoc()) {
        $businesses[] = $business;
    }
} else {
    $query = "SELECT id AS business_id, name FROM business WHERE id = ? AND owner_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $selectedBusiness, $owner_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $businesses[] = $row;
    }
}

$data = [];

foreach ($businesses as $business) {
    $businessId = $business['business_id'];
    $businessName = $business['name'];

    // Sales
    if ($selectedBranch === 'all') {
        // Business-level sales
        $salesQuery = "
            SELECT IFNULL(SUM(total_sales), 0) AS total_sales
            FROM sales
            WHERE product_id IN (SELECT id FROM products WHERE business_id = ?)
              AND branch_id = 0
              AND type = 'business'
        ";
        $stmtSales = $conn->prepare($salesQuery);
        $stmtSales->bind_param("i", $businessId);
        $stmtSales->execute();
        $salesResult = $stmtSales->get_result();
        $salesRow = $salesResult->fetch_assoc();
        $businessSales = $salesRow['total_sales'];

        // Branch sales
        $branchSalesQuery = "
            SELECT IFNULL(SUM(s.total_sales), 0) AS total_branch_sales
            FROM sales s
            INNER JOIN branch br ON br.id = s.branch_id
            WHERE br.business_id = ? AND s.type = 'branch'
        ";
        $stmtBranchSales = $conn->prepare($branchSalesQuery);
        $stmtBranchSales->bind_param("i", $businessId);
        $stmtBranchSales->execute();
        $branchSalesResult = $stmtBranchSales->get_result();
        $branchSalesRow = $branchSalesResult->fetch_assoc();
        $branchSales = $branchSalesRow['total_branch_sales'];

        $totalSales = floatval($businessSales) + floatval($branchSales);
    } else {
        // Specific branch sales only
        $salesQuery = "
            SELECT IFNULL(SUM(s.total_sales), 0) AS total_sales
            FROM sales s
            WHERE s.branch_id = ? AND s.type = 'branch'
        ";
        $stmtSales = $conn->prepare($salesQuery);
        $stmtSales->bind_param("i", $selectedBranch);
        $stmtSales->execute();
        $salesResult = $stmtSales->get_result();
        $salesRow = $salesResult->fetch_assoc();
        $totalSales = floatval($salesRow['total_sales']);
    }

    // Expenses
    if ($selectedBranch === 'all') {
        $expensesQuery = "
            SELECT IFNULL(SUM(amount), 0) AS total_expenses
            FROM expenses
            WHERE (category = 'business' AND category_id = ?)
               OR (category = 'branch' AND category_id IN (SELECT id FROM branch WHERE business_id = ?))
        ";
        $stmtExpenses = $conn->prepare($expensesQuery);
        $stmtExpenses->bind_param("ii", $businessId, $businessId);
    } else {
        $expensesQuery = "
            SELECT IFNULL(SUM(amount), 0) AS total_expenses
            FROM expenses
            WHERE category = 'branch' AND category_id = ?
        ";
        $stmtExpenses = $conn->prepare($expensesQuery);
        $stmtExpenses->bind_param("i", $selectedBranch);
    }
    $stmtExpenses->execute();
    $expensesResult = $stmtExpenses->get_result();
    $expensesRow = $expensesResult->fetch_assoc();
    $totalExpenses = $expensesRow['total_expenses'];

    $data[] = [
        'name' => $businessName,
        'sales' => round($totalSales, 2),
        'expenses' => round(floatval($totalExpenses), 2)
    ];
}

header('Content-Type: application/json');
echo json_encode($data);
