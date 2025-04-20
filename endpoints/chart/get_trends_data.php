<?php
$owner_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
if (isset($_GET['id']) && intval($_GET['id']) > 0) {
    $owner_id = intval($_GET['id']);
}

include '../../conn/conn.php';

// Check owner existence
$ownerCheck = $conn->prepare("SELECT id FROM owner WHERE id = ?");
$ownerCheck->bind_param("i", $owner_id);
$ownerCheck->execute();
$ownerResult = $ownerCheck->get_result();
if ($ownerResult->num_rows === 0) {
    die('Owner not found.');
}

// Get filters
$businessId = $_GET['business'] ?? 'all';
$branchId = $_GET['branch'] ?? 'all';

$extraSalesWhere = '';
$extraExpenseWhere = '';

// Apply business filter
if ($businessId !== 'all') {
    $extraSalesWhere .= " AND p.business_id = " . intval($businessId);
    $extraExpenseWhere .= " AND (
        (e.category = 'business' AND e.category_id = " . intval($businessId) . ")
        OR
        (e.category = 'branch' AND EXISTS (
            SELECT 1 FROM products p2 WHERE p2.business_id = " . intval($businessId) . " AND p2.id = (
                SELECT product_id FROM sales s2 WHERE s2.branch_id = e.category_id LIMIT 1
            )
        ))
    )";
}

// Apply branch filter
if ($branchId !== 'all') {
    if ($branchId == '0') {
        // Only business-level records (type = 'business')
        $extraSalesWhere .= " AND s.type = 'business'";
        $extraExpenseWhere .= " AND e.category = 'business'";
    } else {
        // Only specific branch (type = 'branch')
        $extraSalesWhere .= " AND s.branch_id = " . intval($branchId) . " AND s.type = 'branch'";
        $extraExpenseWhere .= " AND e.category = 'branch' AND e.category_id = " . intval($branchId);
    }
}

// Final Query
$chartQuery = "
    WITH RECURSIVE months AS (
        SELECT DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 11 MONTH), '%Y-%m-01') AS date
        UNION ALL
        SELECT DATE_ADD(date, INTERVAL 1 MONTH)
        FROM months
        WHERE DATE_ADD(date, INTERVAL 1 MONTH) <= CURDATE()
    ),
    sales_data AS (
        SELECT 
            DATE_FORMAT(s.created_at, '%Y-%m') AS month,
            SUM(CAST(s.total_sales AS DECIMAL(10,2))) AS total_sales,
            SUM(CAST(s.quantity AS DECIMAL(10,2)) * CAST(p.price AS DECIMAL(10,2))) AS cost
        FROM sales s
        JOIN products p ON s.product_id = p.id
        JOIN business b ON p.business_id = b.id
        WHERE b.owner_id = ?
        $extraSalesWhere
        GROUP BY month
    ),
    expense_data AS (
        SELECT 
            DATE_FORMAT(e.created_at, '%Y-%m') AS month,
            SUM(CAST(e.amount AS DECIMAL(10,2))) AS total_expenses
        FROM expenses e
        WHERE e.owner_id = ?
        $extraExpenseWhere
        GROUP BY month
    )
    SELECT 
        m.date AS month,
        COALESCE(s.total_sales, 0) AS sales,
        COALESCE(e.total_expenses, 0) AS expenses,
        COALESCE(s.total_sales - s.cost, 0) AS profit
    FROM months m
    LEFT JOIN sales_data s ON m.date = CONCAT(s.month, '-01')
    LEFT JOIN expense_data e ON m.date = CONCAT(e.month, '-01')
    ORDER BY m.date
";

$stmt = $conn->prepare($chartQuery);
$stmt->bind_param("ii", $owner_id, $owner_id);
$stmt->execute();
$result = $stmt->get_result();

// Process results
$labels = $sales = $expenses = $profits = $margins = [];
while ($row = $result->fetch_assoc()) {
    $labels[] = date("F Y", strtotime($row['month']));
    $sales[] = (float) $row['sales'];
    $expenses[] = (float) $row['expenses'];
    $profits[] = (float) $row['profit'];
    $margins[] = ($row['sales'] > 0) ? round(($row['profit'] / $row['sales']) * 100, 2) : 0;
}

$data = [
    'labels' => $labels,
    'sales' => $sales,
    'expenses' => $expenses,
    'profits' => $profits,
    'margins' => $margins
];

header('Content-Type: application/json');
echo json_encode($data);
?>