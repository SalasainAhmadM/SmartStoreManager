<?php
session_start();
require_once '../conn/auth.php';
require_once '../conn/conn.php';
validateSession('manager');
date_default_timezone_set('Asia/Manila');
$manager_id = $_SESSION['user_id'];
$selected_date = $_GET['date'];

// Query to fetch the assigned branch or business
$sql = "
    SELECT 'branch' AS type, b.id, b.location AS name, b.business_id, bs.name AS business_name
    FROM branch b
    LEFT JOIN business bs ON b.business_id = bs.id
    WHERE b.manager_id = ?
    UNION
    SELECT 'business' AS type, id, name, NULL AS business_id, NULL AS business_name
    FROM business
    WHERE manager_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $manager_id, $manager_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $assigned = $result->fetch_assoc();

    // Determine the type and details of the assignment
    if ($assigned['type'] === 'branch') {
        $assigned_type = 'Branch';
        $assigned_name = $assigned['name'];
        $business_name = $assigned['business_name'];
    } else {
        $assigned_type = 'Business';
        $assigned_name = $assigned['name'];
        $business_name = null;
    }
} else {
    // No assignment found
    $assigned_type = null;
    $assigned_name = null;
    $business_id = null;
}

$sales_query = "";

if ($assigned_type === 'Branch') {
    $sales_query = "
        SELECT 
            s.id, 
            p.name AS product, 
            p.price, 
            s.quantity, 
            (s.quantity * p.price) AS revenue, 
            s.date 
        FROM sales s
        LEFT JOIN products p ON s.product_id = p.id
        WHERE s.type = 'branch' AND s.branch_id = ? AND s.user_role != 'Owner' AND DATE(s.date) = ?
        ORDER BY s.date DESC
    ";
    $stmt = $conn->prepare($sales_query);
    $stmt->bind_param('is', $assigned['id'], $selected_date);
} elseif ($assigned_type === 'Business') {
    $sales_query = "
        SELECT 
            s.id, 
            p.name AS product, 
            p.price, 
            s.quantity, 
            (s.quantity * p.price) AS revenue, 
            s.date 
        FROM sales s
        LEFT JOIN products p ON s.product_id = p.id
        WHERE s.type = 'business' AND s.branch_id = 0 AND s.user_role != 'Owner' AND DATE(s.date) = ?
        ORDER BY s.date DESC
    ";
    $stmt = $conn->prepare($sales_query);
    $stmt->bind_param('s', $selected_date);
}

$stmt->execute();
$sales_result = $stmt->get_result();

$sales_data = [];
while ($row = $sales_result->fetch_assoc()) {
    $sales_data[] = $row;
}

echo json_encode($sales_data);
?>