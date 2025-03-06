<?php
header('Content-Type: application/json');
require_once '../../conn/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $business_id = $_POST['business_id'] ?? null;

    if (!$business_id) {
        echo json_encode(['success' => false, 'message' => 'Missing business ID.']);
        exit;
    }

    $query = "SELECT p.id, p.name, p.size, p.price, 
                     IFNULL(pa.status, 'Available') AS status
              FROM products p
              LEFT JOIN product_availability pa 
              ON p.id = pa.product_id 
              AND pa.business_id = ? 
              AND pa.branch_id IS NULL
              WHERE p.business_id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $business_id, $business_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }

    echo json_encode(['success' => true, 'products' => $products]);

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>