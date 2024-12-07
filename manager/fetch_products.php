<?php
require_once '../conn/conn.php';

// Get the business_id from the query parameter
header('Content-Type: application/json');

if (isset($_GET['business_id'])) {
    $businessId = intval($_GET['business_id']);

    $stmt = $conn->prepare("SELECT id, name, description, price FROM products WHERE business_id = ?");
    $stmt->bind_param("i", $businessId);
    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }

    echo json_encode($products);

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['error' => 'Invalid request']);
}
?>