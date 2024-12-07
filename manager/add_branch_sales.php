<?php
require_once '../conn/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $product_id = $data['product_id'] ?? null;
    $quantity = $data['quantity'] ?? null;
    $total_sales = $data['total_sales'] ?? null;
    $date = $data['date'] ?? null;
    $branch_id = $data['branch_id'] ?? null;

    // Validate required fields
    if (!$product_id || !$quantity || !$total_sales || !$date || (!$branch_id && $branch_id !== 0)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
        exit;
    }


    $user_role = 'Manager';
    $type = 'branch';

    $stmt = $conn->prepare("INSERT INTO `sales` (quantity, total_sales, date, product_id, branch_id, user_role, type) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('ssssiss', $quantity, $total_sales, $date, $product_id, $branch_id, $user_role, $type);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Branch sales added successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add sales.']);
    }

    $stmt->close();
    $conn->close();
}
?>