<?php
session_start();
require_once '../../conn/auth.php';
require_once '../../conn/conn.php';

validateSession('owner');

$owner_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
if (isset($_GET['id']) && intval($_GET['id']) > 0) {
    $owner_id = intval($_GET['id']);
}

$selectedBusiness = $_GET['business'] ?? 'all';
$selectedBranch = $_GET['branch'] ?? 'all';

// Add business filtering to the query
$whereClauses = ["b.owner_id = ?"];
$params = [$owner_id];
$types = "i";

if ($selectedBusiness !== 'all') {
    $whereClauses[] = "p.business_id = ?";
    $params[] = $selectedBusiness;
    $types .= "i";
}

if ($selectedBranch !== 'all') {
    $whereClauses[] = "s.branch_id = ?";
    $whereClauses[] = "s.type = 'branch'";
    $params[] = $selectedBranch;
    $types .= "i";
} else {
    $whereClauses[] = "(s.type = 'business' OR s.type = 'branch')";
}

// Last 30 days filter
$whereClauses[] = "s.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";

$query = "
    SELECT 
        p.name AS product_name,
        b.name AS business_name,
        SUM(s.total_sales) AS revenue,
        COUNT(*) AS units_sold,
        SUM(s.total_sales) - (p.price * COUNT(*)) AS profit
    FROM sales s
    JOIN products p ON s.product_id = p.id
    JOIN business b ON p.business_id = b.id
    WHERE " . implode(' AND ', $whereClauses) . "
    GROUP BY p.id, p.name, b.name
    ORDER BY revenue DESC
";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = [
        'product_name' => $row['product_name'],
        'business_name' => $row['business_name'],
        'revenue' => (float) $row['revenue'],
        'units_sold' => (int) $row['units_sold'],
        'profit' => (float) $row['profit']
    ];
}

echo json_encode($products);
?>