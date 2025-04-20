<?php
require_once '../../conn/conn.php';
header('Content-Type: application/json');

session_start(); // Ensure session is started

$owner_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
if (isset($_GET['id']) && intval($_GET['id']) > 0) {
    $owner_id = intval($_GET['id']);
}

// Optional filters
$selectedBusiness = $_GET['business'] ?? 'all';
$selectedBranch = $_GET['branch'] ?? 'all';

// Validate owner
$query = "SELECT id FROM owner WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die(json_encode(['error' => 'Owner not found.']));
}

// === CHART 1: SALES AND EXPENSES BAR CHART ===

$filters = [];
$params = [];

if ($selectedBusiness !== 'all') {
    $filters[] = "b.id = ?";
    $params[] = $selectedBusiness;
} else {
    $filters[] = "b.owner_id = ?";
    $params[] = $owner_id;
}

$filterSQL = implode(' AND ', $filters);

// Dynamic UNION query
$businessQuery = "SELECT b.id AS business_id, b.name AS business_name, NULL AS branch_id, 'Main Branch' AS branch_location
                  FROM business b
                  WHERE $filterSQL
                  UNION ALL
                  SELECT b.id AS business_id, b.name AS business_name, br.id AS branch_id, br.location AS branch_location
                  FROM business b
                  JOIN branch br ON br.business_id = b.id
                  WHERE $filterSQL";

$stmt = $conn->prepare($businessQuery);
$stmt->bind_param(str_repeat('i', count($params) * 2), ...$params, ...$params); // for both parts of UNION
$stmt->execute();
$businessResult = $stmt->get_result();

$labels = [];
$salesData = [];
$expensesData = [];

while ($row = $businessResult->fetch_assoc()) {
    $business_id = $row['business_id'];
    $branch_id = $row['branch_id'];
    $isBranch = !is_null($branch_id);

    // Apply branch filter (skip other branches if selectedBranch is set)
    if ($selectedBranch !== 'all' && $isBranch && $branch_id != $selectedBranch) {
        continue;
    }

    // Label
    $label = $isBranch ? $row['branch_location'] : $row['business_name'] . ' - ' . $row['branch_location'];
    $labels[] = $label;

    // SALES
    if ($isBranch) {
        $salesQuery = "SELECT SUM(s.total_sales) AS total
                       FROM sales s
                       JOIN products p ON p.id = s.product_id
                       WHERE s.branch_id = ? AND s.type = 'branch' AND p.business_id = ?";
        $stmtSales = $conn->prepare($salesQuery);
        $stmtSales->bind_param("ii", $branch_id, $business_id);
    } else {
        $salesQuery = "SELECT SUM(s.total_sales) AS total
                       FROM sales s
                       JOIN products p ON p.id = s.product_id
                       WHERE s.branch_id = 0 AND s.type = 'business' AND p.business_id = ?";
        $stmtSales = $conn->prepare($salesQuery);
        $stmtSales->bind_param("i", $business_id);
    }

    $stmtSales->execute();
    $salesTotal = $stmtSales->get_result()->fetch_assoc()['total'] ?? 0;
    $salesData[] = floatval($salesTotal);

    // EXPENSES
    $category = $isBranch ? 'branch' : 'business';
    $category_id = $isBranch ? $branch_id : $business_id;
    $stmtExp = $conn->prepare("SELECT SUM(amount) AS total FROM expenses WHERE owner_id = ? AND category = ? AND category_id = ?");
    $stmtExp->bind_param("isi", $owner_id, $category, $category_id);
    $stmtExp->execute();
    $expTotal = $stmtExp->get_result()->fetch_assoc()['total'] ?? 0;
    $expensesData[] = floatval($expTotal);
}

// === CHART 2: SALES VS EXPENSES OVER TIME ===

$salesOverTime = [];
$salesQuery = "SELECT DATE(s.date) AS date, SUM(s.total_sales) AS total
               FROM sales s
               JOIN products p ON p.id = s.product_id
               WHERE 1=1";
$salesParams = [];

if ($selectedBusiness !== 'all') {
    $salesQuery .= " AND p.business_id = ?";
    $salesParams[] = $selectedBusiness;
} else {
    $salesQuery .= " AND p.business_id IN (SELECT id FROM business WHERE owner_id = ?)";
    $salesParams[] = $owner_id;
}

if ($selectedBranch !== 'all') {
    $salesQuery .= " AND s.branch_id = ?";
    $salesParams[] = $selectedBranch;
}

$salesQuery .= " GROUP BY DATE(s.date) ORDER BY DATE(s.date) ASC";

$stmt = $conn->prepare($salesQuery);
$stmt->bind_param(str_repeat('i', count($salesParams)), ...$salesParams);
$stmt->execute();
$salesResult = $stmt->get_result();
while ($row = $salesResult->fetch_assoc()) {
    $salesOverTime[$row['date']] = floatval($row['total']);
}

// EXPENSES
$expensesOverTime = [];
$expenseQuery = "SELECT DATE(created_at) AS date, SUM(amount) AS total FROM expenses WHERE owner_id = ?";
$expenseParams = [$owner_id];

if ($selectedBranch !== 'all') {
    $expenseQuery .= " AND category = 'branch' AND category_id = ?";
    $expenseParams[] = $selectedBranch;
} elseif ($selectedBusiness !== 'all') {
    $expenseQuery .= " AND (
        (category = 'business' AND category_id = ?)
        OR
        (category = 'branch' AND category_id IN (SELECT id FROM branch WHERE business_id = ?))
    )";
    $expenseParams[] = $selectedBusiness;
    $expenseParams[] = $selectedBusiness;
} else {
    $expenseQuery .= " AND (
        (category = 'business' AND category_id IN (SELECT id FROM business WHERE owner_id = ?))
        OR
        (category = 'branch' AND category_id IN (SELECT id FROM branch WHERE business_id IN (SELECT id FROM business WHERE owner_id = ?)))
    )";
    $expenseParams[] = $owner_id;
    $expenseParams[] = $owner_id;
}

$expenseQuery .= " GROUP BY DATE(created_at) ORDER BY DATE(created_at) ASC";

$stmt = $conn->prepare($expenseQuery);
$stmt->bind_param(str_repeat('i', count($expenseParams)), ...$expenseParams);
$stmt->execute();
$expenseResult = $stmt->get_result();
while ($row = $expenseResult->fetch_assoc()) {
    $expensesOverTime[$row['date']] = floatval($row['total']);
}

// Merge dates for Chart 2
$allDates = array_unique(array_merge(array_keys($salesOverTime), array_keys($expensesOverTime)));
sort($allDates);
$totalSales = [];
$totalExpenses = [];
foreach ($allDates as $date) {
    $totalSales[] = $salesOverTime[$date] ?? 0;
    $totalExpenses[] = $expensesOverTime[$date] ?? 0;
}

// Final Output
echo json_encode([
    'labels' => $labels,
    'sales' => $salesData,
    'expenses' => $expensesData,
    'dates' => $allDates,
    'salesOverTime' => $totalSales,
    'expensesOverTime' => $totalExpenses
]);
?>