<?php
session_start();
require_once '../../conn/auth.php';
require_once '../../conn/conn.php';
validateSession('owner');

header('Content-Type: application/json');

// Set timezone
date_default_timezone_set('Asia/Manila');

// Use today's date if no date is provided
$data = json_decode(file_get_contents('php://input'), true);
$date = $data['date'] ?? date("Y-m-d");

$owner_id = $_SESSION['user_id'];

// Fetch sales for the specified date
$query = "
    SELECT 
        p.name AS product_name,
        p.price AS product_price,
        s.quantity,
        s.total_sales,
        b.name AS business_name,
        s.date
    FROM sales s
    JOIN products p ON s.product_id = p.id
    JOIN business b ON p.business_id = b.id
    WHERE b.owner_id = ? AND s.date = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("is", $owner_id, $date);
$stmt->execute();
$result = $stmt->get_result();

$sales_data = [];
while ($row = $result->fetch_assoc()) {
    $sales_data[] = $row;
}

echo json_encode(['success' => true, 'sales' => $sales_data]);
exit;
