<?php
session_start();
require_once '../../conn/auth.php';
require_once '../../conn/conn.php';

validateSession('owner');

$owner_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
if (isset($_GET['id']) && intval($_GET['id']) > 0) {
    $owner_id = intval($_GET['id']);
}

$selectedBusiness = isset($_GET['business']) && $_GET['business'] !== 'all' ? intval($_GET['business']) : 'all';
$selectedBranch = isset($_GET['branch']) && $_GET['branch'] !== 'all' ? intval($_GET['branch']) : 'all';

// Get previous and current month
$currentDate = new DateTime();
$currentMonth = $currentDate->format('Y-m');
$previousMonth = $currentDate->modify('-1 month')->format('Y-m');

// Only 2 months needed
$months = [$previousMonth, $currentMonth];

// Initialize data array
$data = [];
foreach ($months as $month) {
    $data[$month] = [
        'month' => $month,
        'sales' => 0,
        'expenses' => 0,
        'profit' => 0,
    ];
}

// Fetch sales
$salesQuery = "
    SELECT 
        s.total_sales, 
        DATE_FORMAT(s.date, '%Y-%m') AS sales_month
    FROM sales s
    INNER JOIN products p ON s.product_id = p.id
    INNER JOIN business b ON p.business_id = b.id
    WHERE 1
      AND s.type IN ('business', 'branch')
      AND DATE_FORMAT(s.date, '%Y-%m') IN (?, ?)
      AND b.owner_id = ?
";

$params = [$previousMonth, $currentMonth, $owner_id];
$types = "ssi";

if ($selectedBusiness !== 'all') {
    $salesQuery .= " AND p.business_id = ?";
    $params[] = $selectedBusiness;
    $types .= "i";
}

if ($selectedBranch !== 'all') {
    $salesQuery .= " AND s.branch_id = ?";
    $params[] = $selectedBranch;
    $types .= "i";
}

$stmt = $conn->prepare($salesQuery);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    if (isset($data[$row['sales_month']])) {
        $data[$row['sales_month']]['sales'] += floatval($row['total_sales']);
    }
}
$stmt->close();

// Fetch expenses
$expensesQuery = "
    SELECT 
        e.amount, 
        DATE_FORMAT(e.created_at, '%Y-%m') AS expense_month
    FROM expenses e
    WHERE e.category IN ('business', 'branch')
      AND e.owner_id = ?
      AND DATE_FORMAT(e.created_at, '%Y-%m') IN (?, ?)
";

$expensesParams = [$owner_id, $previousMonth, $currentMonth];
$expensesTypes = "iss";

if ($selectedBusiness !== 'all') {
    if ($selectedBranch !== 'all') {
        $expensesQuery .= " AND e.category = 'branch' AND e.category_id = ?";
        $expensesParams[] = $selectedBranch;
        $expensesTypes .= "i";
    } else {
        $expensesQuery .= " AND e.category = 'business' AND e.category_id = ?";
        $expensesParams[] = $selectedBusiness;
        $expensesTypes .= "i";
    }
}

$stmt = $conn->prepare($expensesQuery);
$stmt->bind_param($expensesTypes, ...$expensesParams);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    if (isset($data[$row['expense_month']])) {
        $data[$row['expense_month']]['expenses'] += floatval($row['amount']);
    }
}
$stmt->close();

// Calculate profit
foreach ($data as &$d) {
    $d['profit'] = $d['sales'] - $d['expenses'];
}
unset($d);

header('Content-Type: application/json');
echo json_encode(array_values($data));
?>