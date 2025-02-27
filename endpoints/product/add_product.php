<?php
header('Content-Type: application/json');
require_once '../../conn/conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['business_id'], $data['name'], $data['type'], $data['size'], $data['price'], $data['description'])) {
        $businessId = intval($data['business_id']);
        $name = $data['name'];
        $type = $data['type'];
        $size = $data['size'];
        $price = floatval($data['price']);
        $description = $data['description'];

        $query = "INSERT INTO products (business_id, name, type, size, price, description) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isssss", $businessId, $name, $type, $size, $price, $description);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add product.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$stmt->close();
$conn->close();
?>