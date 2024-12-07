<?php
require_once '../conn/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sale_id = $_POST['sale_id'] ?? null;
    $product_id = $_POST['product_id'] ?? null;
    $quantity = $_POST['quantity'] ?? null;
    $total_sales = $_POST['total_sales'] ?? null;
    $date = $_POST['date'] ?? null;

    if (!$sale_id || !$product_id || !$quantity || !$total_sales || !$date) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }

    $stmt = $conn->prepare("
        UPDATE sales 
        SET product_id = ?, quantity = ?, total_sales = ?, date = ? 
        WHERE id = ?
    ");
    $stmt->bind_param('isdsi', $product_id, $quantity, $total_sales, $date, $sale_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Sale updated successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update sale.']);
    }

    $stmt->close();
    $conn->close();
}
?>