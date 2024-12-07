<?php
require_once '../conn/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sale_id = $_POST['sale_id'] ?? null;

    if (!$sale_id) {
        echo json_encode(['success' => false, 'message' => 'Sale ID is required.']);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT 
            s.id, s.quantity, s.date, 
            p.name AS product
        FROM sales s
        LEFT JOIN products p ON s.product_id = p.id
        WHERE s.id = ?
    ");
    $stmt->bind_param('i', $sale_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['success' => true, 'sale' => $result->fetch_assoc()]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Sale not found.']);
    }

    $stmt->close();
    $conn->close();
}

?>