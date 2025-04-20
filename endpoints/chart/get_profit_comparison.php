<?php
$owner_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
if (isset($_GET['id']) && intval($_GET['id']) > 0) {
    $owner_id = intval($_GET['id']);
}

$selectedBusiness = $_GET['business'] ?? 'all';
$selectedBranch = $_GET['branch'] ?? 'all';

include '../../conn/conn.php';

// Validate owner
$ownerCheck = $conn->prepare("SELECT id FROM owner WHERE id = ?");
$ownerCheck->bind_param("i", $owner_id);
$ownerCheck->execute();
$ownerResult = $ownerCheck->get_result();
if ($ownerResult->num_rows === 0) {
    die(json_encode(['error' => 'Owner not found.']));
}

// Dynamic WHERE clauses
$businessCondition = "";
$branchCondition = "";

$params = [];
$types = "";

// Filter by business
if ($selectedBusiness !== 'all') {
    $businessCondition = "AND p.business_id = ?";
    $params[] = $selectedBusiness;
    $types .= "i";
} else {
    $businessCondition = "AND p.business_id IN (SELECT id FROM business WHERE owner_id = ?)";
    $params[] = $owner_id;
    $types .= "i";
}

// Filter by branch
if ($selectedBranch !== 'all') {
    $branchCondition = "AND s.branch_id = ?";
    $params[] = $selectedBranch;
    $types .= "i";
}

// Query total sales
$salesQuery = "
    SELECT SUM(CAST(s.total_sales AS DECIMAL(10,2))) AS total_sales
    FROM sales s
    JOIN products p ON s.product_id = p.id
    WHERE 1=1 $businessCondition $branchCondition
";
$salesStmt = $conn->prepare($salesQuery);
$salesStmt->bind_param($types, ...$params);
$salesStmt->execute();
$salesResult = $salesStmt->get_result()->fetch_assoc();
$totalSales = (float) $salesResult['total_sales'];

// Reset params for expenses query
$params = [$owner_id];
$types = "i";

// Optional: You could also apply filters to expenses if needed
$expensesQuery = "SELECT SUM(CAST(amount AS DECIMAL(10,2))) AS total_expenses FROM expenses WHERE owner_id = ?";
$expensesStmt = $conn->prepare($expensesQuery);
$expensesStmt->bind_param($types, ...$params);
$expensesStmt->execute();
$expensesResult = $expensesStmt->get_result()->fetch_assoc();
$totalExpenses = (float) $expensesResult['total_expenses'];

$profit = $totalSales - $totalExpenses;
$profitMargin = ($totalSales > 0) ? round(($profit / $totalSales) * 100, 2) : 0;

$response = [
    'profit' => round($profit, 2),
    'margin' => $profitMargin
];

echo json_encode($response);
