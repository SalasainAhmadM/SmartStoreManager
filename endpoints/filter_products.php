<?php
session_start();
require_once '../conn/conn.php';

// Validate the session and owner ID
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['error' => 'Unauthorized access.']));
}

$owner_id = intval($_SESSION['user_id']);
$selectedMonth = isset($_GET['month']) ? intval($_GET['month']) : 0; // Default to "All Time"
$year = date('Y'); // Automatically select the current year

if ($selectedMonth == 0) {
    // If "All Time" is selected, calculate total sales for all time
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
    // Filter by selected month and year
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
    AND MONTH(s.date) = ? 
    AND YEAR(s.date) = ?
    GROUP BY p.name, b.name, p.type, p.price, p.description
    ORDER BY total_sales DESC
    LIMIT 10"; // Limit to top 10 products
}

$stmt = $conn->prepare($sql);

if ($selectedMonth == 0) {
    $stmt->bind_param("i", $owner_id);
} else {
    $stmt->bind_param("iii", $owner_id, $selectedMonth, $year);
}

$stmt->execute();
$result = $stmt->get_result();

$popularProducts = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $popularProducts[] = $row;
    }
}

echo json_encode($popularProducts);
?>