<?php
require_once '../conn/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $business_id = $_POST['business_id'] ?? null;
    $product_id = $_POST['product_id'] ?? null;
    $quantity = $_POST['quantity'] ?? null;
    $total_sales = $_POST['total_sales'] ?? null;
    $date = $_POST['date'] ?? null;

    // Validate required fields
    if (!$business_id || !$product_id || !$quantity || !$total_sales || !$date) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
        exit;
    }

    $branch_id = 0; // Business default
    $user_role = 'Manager';
    $type = 'business';

    $stmt = $conn->prepare("
        INSERT INTO `sales` (quantity, total_sales, date, product_id, branch_id, user_role, type) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('sssiiss', $quantity, $total_sales, $date, $product_id, $branch_id, $user_role, $type);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Business sales added successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to add sales.']);
    }

    $stmt->close();
    $conn->close();
}
?>