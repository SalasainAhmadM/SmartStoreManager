<?php
header('Content-Type: application/json');
require_once '../../conn/conn.php';

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$business_id = $data['business_id'];
$name = $data['name'];
$type = $data['type'];
$price = $data['price'];
$description = $data['description'];

if (!$business_id || !$name || !$type || !$price || !$description) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

$sql = "INSERT INTO products (name, description, price, type, business_id, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ssssi', $name, $description, $price, $type, $business_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add product']);
}

$stmt->close();
$conn->close();
?>