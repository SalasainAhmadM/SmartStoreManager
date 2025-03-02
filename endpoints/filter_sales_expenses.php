<?php
session_start();
require_once '../conn/conn.php';

// Validate input
$ownerId = isset($_GET['owner_id']) ? intval($_GET['owner_id']) : 0;
$selectedMonth = isset($_GET['month']) ? intval($_GET['month']) : 0;

if ($ownerId <= 0) {
    die(json_encode(['error' => 'Invalid owner ID.']));
}

// Fetch filtered data based on the selected month
$year = date('Y'); // Current year

if ($selectedMonth == 0) {
    // Fetch data for the past 30 days
    $sql = "
        SELECT
            DATE(s.created_at) AS date,
            b.name AS business_name,
            COALESCE(SUM(s.total_sales), 0) AS daily_sales,
            COALESCE(SUM(e.amount), 0) AS daily_expenses
        FROM sales s
        LEFT JOIN expenses e ON DATE(s.created_at) = DATE(e.created_at)
        LEFT JOIN products p ON s.product_id = p.id
        LEFT JOIN business b ON p.business_id = b.id
        WHERE b.owner_id = ?
        AND s.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(s.created_at), b.name
        ORDER BY DATE(s.created_at) ASC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $ownerId);
} else {
    // Fetch data for the selected month
    $sql = "
        SELECT
            DATE(s.created_at) AS date,
            b.name AS business_name,
            COALESCE(SUM(s.total_sales), 0) AS daily_sales,
            COALESCE(SUM(e.amount), 0) AS daily_expenses
        FROM sales s
        LEFT JOIN expenses e ON DATE(s.created_at) = DATE(e.created_at)
        LEFT JOIN products p ON s.product_id = p.id
        LEFT JOIN business b ON p.business_id = b.id
        WHERE b.owner_id = ?
        AND MONTH(s.created_at) = ?
        AND YEAR(s.created_at) = ?
        GROUP BY DATE(s.created_at), b.name
        ORDER BY DATE(s.created_at) ASC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $ownerId, $selectedMonth, $year);
}

$stmt->execute();
$result = $stmt->get_result();

$filteredData = [];
while ($row = $result->fetch_assoc()) {
    $filteredData[] = [
        'date' => $row['date'],
        'business_name' => $row['business_name'],
        'sales' => floatval($row['daily_sales']),
        'expenses' => floatval($row['daily_expenses'])
    ];
}

echo json_encode($filteredData);
?>