<?php
require_once '../conn/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sale_id = $_POST['sale_id'] ?? null;

    if (!$sale_id) {
        echo json_encode(['status' => 'error', 'message' => 'Sale ID is required.']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM sales WHERE id = ?");
    $stmt->bind_param('i', $sale_id);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Sale deleted successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete sale.']);
    }

    $stmt->close();
    $conn->close();
}
?>